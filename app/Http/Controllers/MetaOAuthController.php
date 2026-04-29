<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaOAuthController extends Controller
{
    protected string $graphUrl = 'https://graph.facebook.com/v25.0';

    /**
     * Facebook OAuthフローを開始（FB + IG 同時連携）
     */
    public function redirect(Request $request, Store $store)
    {
        $appId = config('services.facebook.app_id');
        if (!$appId) {
            return back()->with('error', 'Facebook App IDが設定されていません。システム管理者に連絡してください。');
        }

        // 戻り先URLをリクエストから受け取る（admin or store）
        $returnUrl = $request->input('return_url', url("/admin/stores/{$store->id}/edit?tab=integrations"));

        // state パラメータに暗号化して格納（CSRF対策）
        $state = Crypt::encryptString(json_encode([
            'store_id'   => $store->id,
            'return_url' => $returnUrl,
            'user_id'    => auth()->id(),
        ]));

        $callbackUrl = url('/meta/callback');

        $scopes = implode(',', [
            'pages_manage_posts',
            'pages_read_engagement',
            'instagram_basic',
            'instagram_content_publish',
            'business_management',
        ]);

        $authUrl = "https://www.facebook.com/v25.0/dialog/oauth?" . http_build_query([
            'client_id'     => $appId,
            'redirect_uri'  => $callbackUrl,
            'scope'         => $scopes,
            'state'         => $state,
            'response_type' => 'code',
        ]);

        return redirect()->away($authUrl);
    }

    /**
     * Facebook OAuthコールバック
     */
    public function callback(Request $request)
    {
        // ユーザーがキャンセルした場合
        if ($request->has('error')) {
            Log::warning('Meta OAuth cancelled', ['error' => $request->input('error')]);
            return redirect(url('/admin'))->with('error', 'Facebook認証がキャンセルされました。');
        }

        // stateの復号・検証
        try {
            $state = json_decode(Crypt::decryptString($request->input('state')), true);
        } catch (\Exception $e) {
            return redirect(url('/admin'))->with('error', '不正なリクエストです。');
        }

        $storeId   = $state['store_id'] ?? null;
        $returnUrl = $state['return_url'] ?? url('/admin');
        $userId    = $state['user_id'] ?? null;

        if (!$storeId || $userId !== auth()->id()) {
            return redirect($returnUrl)->with('error', '不正なリクエストです。');
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect($returnUrl)->with('error', '認証コードが取得できませんでした。');
        }

        // Step 1: 認証コード → 短期ユーザートークン
        $tokenResponse = Http::get("{$this->graphUrl}/oauth/access_token", [
            'client_id'     => config('services.facebook.app_id'),
            'client_secret' => config('services.facebook.app_secret'),
            'redirect_uri'  => url('/meta/callback'),
            'code'          => $code,
        ]);

        if (!$tokenResponse->successful()) {
            Log::error('Meta token exchange failed', ['response' => $tokenResponse->json()]);
            return redirect($returnUrl)->with('error', 'Facebookトークンの取得に失敗しました。');
        }

        $shortLivedToken = $tokenResponse->json('access_token');

        // Step 2: 短期トークン → 長期トークン（60日間有効）
        $longTokenResponse = Http::get("{$this->graphUrl}/oauth/access_token", [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => config('services.facebook.app_id'),
            'client_secret'     => config('services.facebook.app_secret'),
            'fb_exchange_token' => $shortLivedToken,
        ]);

        $longLivedToken = $longTokenResponse->successful()
            ? $longTokenResponse->json('access_token')
            : $shortLivedToken;

        // Step 3: ユーザーが管理するページ一覧を取得（IG Business Account付き）
        $pagesResponse = Http::get("{$this->graphUrl}/me/accounts", [
            'access_token' => $longLivedToken,
            'fields'       => 'id,name,access_token,instagram_business_account{id,name,username}',
        ]);

        if (!$pagesResponse->successful()) {
            Log::error('Meta pages fetch failed', ['response' => $pagesResponse->json()]);
            return redirect($returnUrl)->with('error', 'Facebookページの取得に失敗しました。');
        }

        $pages = $pagesResponse->json('data', []);

        if (empty($pages)) {
            return redirect($returnUrl)->with('error', '管理しているFacebookページが見つかりません。Facebookページを作成してからもう一度お試しください。');
        }

        // ページが1つだけなら自動選択
        if (count($pages) === 1) {
            return $this->connectPage($storeId, $pages[0], $returnUrl);
        }

        // 複数ページ → 選択画面へ（セッションに一時保存）
        session([
            'meta_pages'      => $pages,
            'meta_store_id'   => $storeId,
            'meta_return_url' => $returnUrl,
        ]);

        return redirect(url('/meta/select-page'));
    }

    /**
     * ページ選択画面（複数ページを管理している場合）
     */
    public function selectPage()
    {
        $pages   = session('meta_pages', []);
        $storeId = session('meta_store_id');

        if (empty($pages) || !$storeId) {
            return redirect(url('/admin'))->with('error', 'セッションが切れました。再度連携してください。');
        }

        $store = Store::findOrFail($storeId);

        return view('integrations.select-page', compact('pages', 'store'));
    }

    /**
     * 選択したページを保存
     */
    public function savePage(Request $request)
    {
        $request->validate([
            'page_id' => 'required|string',
        ]);

        $pages     = session('meta_pages', []);
        $storeId   = session('meta_store_id');
        $returnUrl = session('meta_return_url', url('/admin'));

        if (empty($pages) || !$storeId) {
            return redirect(url('/admin'))->with('error', 'セッションが切れました。再度連携してください。');
        }

        $selectedPageId = $request->input('page_id');
        $page = collect($pages)->firstWhere('id', $selectedPageId);

        if (!$page) {
            return redirect(url('/meta/select-page'))->with('error', 'ページが見つかりません。');
        }

        // セッションクリア
        session()->forget(['meta_pages', 'meta_store_id', 'meta_return_url']);

        return $this->connectPage($storeId, $page, $returnUrl);
    }

    /**
     * ページを店舗に接続（Facebook + Instagram自動連携）
     */
    protected function connectPage(int $storeId, array $page, string $returnUrl)
    {
        $messages = [];

        // Facebook連携を保存（ページアクセストークンは長期トークンから取得したもので無期限）
        StoreIntegration::updateOrCreate(
            ['store_id' => $storeId, 'service' => 'facebook'],
            [
                'access_token' => $page['access_token'],
                'extra_data'   => [
                    'page_id'   => $page['id'],
                    'page_name' => $page['name'] ?? '',
                ],
                'is_active' => true,
            ]
        );
        $messages[] = 'Facebook（' . ($page['name'] ?? $page['id']) . '）';

        // Instagram Business Accountが紐付いている場合は自動連携
        $igAccount = $page['instagram_business_account'] ?? null;
        if ($igAccount && !empty($igAccount['id'])) {
            StoreIntegration::updateOrCreate(
                ['store_id' => $storeId, 'service' => 'instagram'],
                [
                    'access_token' => $page['access_token'],
                    'extra_data'   => [
                        'ig_user_id'  => $igAccount['id'],
                        'ig_name'     => $igAccount['name'] ?? '',
                        'ig_username' => $igAccount['username'] ?? '',
                    ],
                    'is_active' => true,
                ]
            );
            $messages[] = 'Instagram（@' . ($igAccount['username'] ?? $igAccount['id']) . '）';
        }

        $msg = implode(' と ', $messages) . ' を連携しました。';
        return redirect($returnUrl)->with('success', $msg);
    }

    /**
     * Meta が App ユーザーから「アプリへのアクセス取り消し」を受けた時に呼ばれるコールバック
     * Meta 仕様: https://developers.facebook.com/docs/development/create-an-app/app-dashboard/data-deletion-callback/
     * Meta App 設定の「Data Deletion Request URL」に登録するエンドポイント
     */
    public function dataDeletionCallback(Request $request)
    {
        $signedRequest = $request->input('signed_request');
        if (!$signedRequest) {
            return response()->json(['error' => 'signed_request missing'], 400);
        }

        $appSecret = config('services.facebook.app_secret');
        $parsed = $this->parseSignedRequest($signedRequest, $appSecret);
        if (!$parsed || empty($parsed['user_id'])) {
            return response()->json(['error' => 'invalid signed_request'], 400);
        }

        $fbUserId = $parsed['user_id'];

        // 該当ユーザーが連携した全店舗のトークンを削除
        // 注: Meta が渡してくる user_id は App-scoped User ID。今のテーブル設計では FB user ID をそのまま保存していないので、
        //     extra_data に保存された page_id 経由で関連レコードを特定できないが、ここでは「リクエスト受領 → 5営業日以内に手動削除」と扱う。
        Log::info('Meta data deletion callback received', [
            'fb_user_id' => $fbUserId,
            'parsed' => $parsed,
        ]);

        // confirmation code を発行
        $code = bin2hex(random_bytes(16));
        \Cache::put('meta_deletion:' . $code, [
            'fb_user_id' => $fbUserId,
            'requested_at' => now()->toDateTimeString(),
            'status' => 'pending',
        ], now()->addDays(30));

        return response()->json([
            'url' => url("/meta/data-deletion-status/{$code}"),
            'confirmation_code' => $code,
        ]);
    }

    /**
     * 削除進捗確認画面（コード付き URL）
     */
    public function dataDeletionStatus(string $code)
    {
        $info = \Cache::get('meta_deletion:' . $code);
        if (!$info) {
            return response("削除リクエストが見つかりません。コードが正しいかご確認ください。", 404)
                ->header('Content-Type', 'text/html; charset=utf-8');
        }
        $status = $info['status'] === 'pending' ? '受付済み（5営業日以内に削除予定）' : '削除完了';
        return response(
            "<html><head><meta charset='utf-8'><title>削除リクエスト状況</title></head>"
            . "<body style='font-family:sans-serif;max-width:600px;margin:40px auto;padding:20px;'>"
            . "<h1>📋 データ削除リクエスト状況</h1>"
            . "<p>確認コード: <code>{$code}</code></p>"
            . "<p>受付日時: {$info['requested_at']}</p>"
            . "<p>状態: <strong>{$status}</strong></p>"
            . "<p>ご質問は <a href='mailto:info@assist-grp.jp'>info@assist-grp.jp</a> までお問い合わせください。</p>"
            . "</body></html>",
            200
        )->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Meta の signed_request を検証して payload を返す
     */
    private function parseSignedRequest(string $signedRequest, string $secret): ?array
    {
        $parts = explode('.', $signedRequest, 2);
        if (count($parts) !== 2) return null;
        [$encodedSig, $payload] = $parts;

        $sig = $this->base64UrlDecode($encodedSig);
        $data = json_decode($this->base64UrlDecode($payload), true);

        if (!is_array($data) || ($data['algorithm'] ?? '') !== 'HMAC-SHA256') {
            return null;
        }

        $expectedSig = hash_hmac('sha256', $payload, $secret, true);
        if (!hash_equals($expectedSig, $sig)) {
            return null;
        }
        return $data;
    }

    private function base64UrlDecode(string $input): string
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}
