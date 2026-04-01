<?php

namespace App\Services;

use App\Models\GoogleReview;
use App\Models\SiteSetting;
use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class GoogleBusinessService
{
    protected string $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
    protected string $tokenUrl = 'https://oauth2.googleapis.com/token';
    protected string $apiBase = 'https://mybusiness.googleapis.com/v4';
    protected string $accountMgmtBase = 'https://mybusinessaccountmanagement.googleapis.com/v1';
    protected string $businessInfoBase = 'https://mybusinessbusinessinformation.googleapis.com/v1';

    /**
     * OAuth認可URLを生成
     */
    public function getAuthUrl(string $redirectUri): string
    {
        $params = http_build_query([
            'client_id' => SiteSetting::get('google_client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/business.manage',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return $this->authUrl . '?' . $params;
    }

    /**
     * 認可コードをアクセストークンに交換
     */
    public function exchangeCode(string $code, string $redirectUri): bool
    {
        $response = Http::post($this->tokenUrl, [
            'code' => $code,
            'client_id' => SiteSetting::get('google_client_id'),
            'client_secret' => SiteSetting::get('google_client_secret'),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            Log::error('Google OAuth token exchange failed', ['body' => $response->body()]);
            return false;
        }

        $data = $response->json();
        SiteSetting::set('google_access_token', $data['access_token']);
        SiteSetting::set('google_refresh_token', $data['refresh_token'] ?? SiteSetting::get('google_refresh_token'));
        SiteSetting::set('google_token_expires_at', now()->addSeconds($data['expires_in'])->toIso8601String());

        return true;
    }

    /**
     * アクセストークンをリフレッシュ
     */
    public function refreshToken(): bool
    {
        $refreshToken = SiteSetting::get('google_refresh_token');
        if (!$refreshToken) {
            return false;
        }

        $response = Http::post($this->tokenUrl, [
            'refresh_token' => $refreshToken,
            'client_id' => SiteSetting::get('google_client_id'),
            'client_secret' => SiteSetting::get('google_client_secret'),
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            Log::error('Google OAuth token refresh failed', ['body' => $response->body()]);
            return false;
        }

        $data = $response->json();
        SiteSetting::set('google_access_token', $data['access_token']);
        SiteSetting::set('google_token_expires_at', now()->addSeconds($data['expires_in'])->toIso8601String());

        return true;
    }

    /**
     * 有効なアクセストークンを取得（期限切れなら自動リフレッシュ）
     */
    protected function getAccessToken(): ?string
    {
        $token = SiteSetting::get('google_access_token');
        $expiresAt = SiteSetting::get('google_token_expires_at');

        if (!$token) {
            return null;
        }

        // 有効期限の5分前にリフレッシュ
        if ($expiresAt && Carbon::parse($expiresAt)->subMinutes(5)->isPast()) {
            if (!$this->refreshToken()) {
                return null;
            }
            $token = SiteSetting::get('google_access_token');
        }

        return $token;
    }

    /**
     * 認証済みHTTPリクエスト
     */
    protected function apiGet(string $url, array $query = [])
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        return Http::withToken($token)->get($url, $query);
    }

    protected function apiPut(string $url, array $data = [])
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        return Http::withToken($token)->put($url, $data);
    }

    protected function apiDelete(string $url)
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        return Http::withToken($token)->delete($url);
    }

    protected function apiPost(string $url, array $data = [])
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        return Http::withToken($token)->post($url, $data);
    }

    protected function apiPostMultipart(string $url, array $multipart = [])
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        $request = Http::withToken($token);
        foreach ($multipart as $item) {
            $request = $request->attach($item['name'], $item['contents'], $item['filename'] ?? null, $item['headers'] ?? []);
        }

        return $request->post($url);
    }

    /**
     * 連携状態を確認
     */
    public function isConnected(): bool
    {
        return (bool) SiteSetting::get('google_refresh_token');
    }

    /**
     * 各APIエンドポイントをテストしてアカウントIDを検出
     */
    public function testApis(): array
    {
        $results = [];

        // 1. Account Management API
        $response = $this->apiGet($this->accountMgmtBase . '/accounts');
        $results['account_management'] = [
            'status' => $response ? $response->status() : 'no_token',
            'body' => $response ? $response->json() : null,
        ];

        // 2. v4 Legacy accounts
        $response = $this->apiGet($this->apiBase . '/accounts');
        $results['v4_legacy'] = [
            'status' => $response ? $response->status() : 'no_token',
            'body' => $response ? $response->json() : null,
        ];

        // 3. Business Information API (locations直接)
        // アカウントIDが既にあれば、ロケーション取得もテスト
        $accountId = SiteSetting::get('google_account_id');
        if ($accountId) {
            $response = $this->apiGet(
                $this->businessInfoBase . "/{$accountId}/locations",
                ['readMask' => 'name,title,storefrontAddress']
            );
            $results['business_info_locations'] = [
                'status' => $response ? $response->status() : 'no_token',
                'body' => $response ? $response->json() : null,
            ];
        }

        // アカウントIDを自動検出
        $detectedAccountId = null;
        if (isset($results['account_management']['body']['accounts'][0]['name'])) {
            $detectedAccountId = $results['account_management']['body']['accounts'][0]['name'];
        } elseif (isset($results['v4_legacy']['body']['accounts'][0]['name'])) {
            $detectedAccountId = $results['v4_legacy']['body']['accounts'][0]['name'];
        }
        $results['detected_account_id'] = $detectedAccountId;

        return $results;
    }

    /**
     * Googleビジネスアカウント一覧を取得
     * Account Management API → v4 Legacy の順で試行
     */
    public function listAccounts(): ?array
    {
        // まず Account Management API を試行
        $response = $this->apiGet($this->accountMgmtBase . '/accounts');
        if ($response && $response->successful()) {
            return $response->json('accounts', []);
        }

        Log::warning('Google API: listAccounts via Account Management API failed, trying v4 legacy', [
            'status' => $response?->status(),
            'body' => $response?->body(),
        ]);

        // フォールバック: v4 Legacy API
        $response = $this->apiGet($this->apiBase . '/accounts');
        if ($response && $response->successful()) {
            return $response->json('accounts', []);
        }

        Log::error('Google API: listAccounts failed on all endpoints', ['body' => $response?->body()]);
        return null;
    }

    /**
     * アカウント配下のロケーション一覧を取得
     * Business Information API（割り当て済み）を使用
     */
    public function listLocations(string $accountName): ?array
    {
        // Business Information API を使用
        $response = $this->apiGet(
            $this->businessInfoBase . "/{$accountName}/locations",
            ['readMask' => 'name,title,storefrontAddress']
        );

        if ($response && $response->successful()) {
            return $response->json('locations', []);
        }

        Log::warning('Google API: listLocations via Business Information API failed, trying v4 legacy', [
            'status' => $response?->status(),
            'body' => $response?->body(),
        ]);

        // フォールバック: v4 Legacy API
        $response = $this->apiGet(
            $this->apiBase . "/{$accountName}/locations",
            ['readMask' => 'name,title,storefrontAddress']
        );

        if ($response && $response->successful()) {
            return $response->json('locations', []);
        }

        Log::error('Google API: listLocations failed on all endpoints', ['body' => $response?->body()]);
        return null;
    }

    /**
     * 指定ロケーションの口コミを取得
     */
    public function fetchReviews(Store $store, string $pageToken = null): ?array
    {
        $accountName = SiteSetting::get('google_account_id');
        $locationName = $store->google_location_name;

        if (!$accountName || !$locationName) {
            return null;
        }

        $url = $this->apiBase . "/{$accountName}/{$locationName}/reviews";
        $query = ['pageSize' => 50];
        if ($pageToken) {
            $query['pageToken'] = $pageToken;
        }

        $response = $this->apiGet($url, $query);

        if (!$response || !$response->successful()) {
            Log::error('Google API: fetchReviews failed', [
                'store' => $store->id,
                'body' => $response?->body(),
            ]);
            return null;
        }

        return $response->json();
    }

    /**
     * 店舗の全口コミを取得してDBに同期（差分同期対応）
     */
    public function syncReviews(Store $store): int
    {
        $synced = 0;
        $pageToken = null;

        do {
            $result = $this->fetchReviews($store, $pageToken);
            if (!$result) {
                break;
            }

            $reviews = $result['reviews'] ?? [];
            $unchangedCount = 0;

            foreach ($reviews as $review) {
                $reviewId = $review['reviewId'] ?? $review['name'] ?? null;
                if (!$reviewId) {
                    continue;
                }

                // 既存口コミの内容を比較し、変更がなければスキップ
                $existing = GoogleReview::where('google_review_id', $reviewId)->first();
                if ($existing) {
                    $apiComment = $review['comment'] ?? null;
                    if ($apiComment) {
                        $apiComment = preg_replace('/\s*\(Translated by Google\).*$/us', '', $apiComment);
                        $apiComment = trim($apiComment) ?: null;
                    }
                    $apiReply = $review['reviewReply']['comment'] ?? null;
                    $ratingMap = ['STAR_RATING_UNSPECIFIED'=>0,'ONE'=>1,'TWO'=>2,'THREE'=>3,'FOUR'=>4,'FIVE'=>5];
                    $apiRating = $ratingMap[$review['starRating'] ?? 'STAR_RATING_UNSPECIFIED'] ?? 0;

                    // 評価・コメント・返信が全て同じなら変更なしと判断
                    if ($existing->rating == $apiRating
                        && $existing->comment === $apiComment
                        && $existing->reply_comment === $apiReply) {
                        $unchangedCount++;
                        continue;
                    }
                }

                if ($this->upsertReview($store, $review)) {
                    $synced++;
                }
            }

            // ページ内の全口コミが既存かつ未変更なら、それ以降のページも同じなので打ち切り
            if ($unchangedCount === count($reviews) && count($reviews) > 0) {
                Log::info("Google sync: 既存口コミに到達、同期を打ち切り", [
                    'store' => $store->name,
                    'synced' => $synced,
                ]);
                break;
            }

            $pageToken = $result['nextPageToken'] ?? null;
        } while ($pageToken);

        return $synced;
    }

    /**
     * Google口コミデータをDBにupsert
     */
    protected function upsertReview(Store $store, array $data): ?GoogleReview
    {
        $ratingMap = [
            'STAR_RATING_UNSPECIFIED' => 0,
            'ONE' => 1,
            'TWO' => 2,
            'THREE' => 3,
            'FOUR' => 4,
            'FIVE' => 5,
        ];

        $reviewId = $data['reviewId'] ?? $data['name'] ?? null;
        if (!$reviewId) {
            Log::warning('Google API: Missing review ID, skipping', ['data' => array_keys($data)]);
            return null;
        }

        $isNew = !GoogleReview::where('google_review_id', $reviewId)->exists();

        $rating = $ratingMap[$data['starRating'] ?? 'STAR_RATING_UNSPECIFIED'] ?? 0;
        $comment = $data['comment'] ?? null;
        // Google翻訳テキスト "(Translated by Google) ..." を除去
        if ($comment) {
            $comment = preg_replace('/\s*\(Translated by Google\).*$/us', '', $comment);
            $comment = trim($comment) ?: null;
        }
        $reviewerName = $data['reviewer']['displayName'] ?? '匿名';
        $reviewerPhoto = $data['reviewer']['profilePhotoUrl'] ?? null;
        $reviewedAt = isset($data['createTime'])
            ? Carbon::parse($data['createTime'])->setTimezone('Asia/Tokyo')
            : now();

        // 返信情報
        $replyComment = $data['reviewReply']['comment'] ?? null;
        $repliedAt = isset($data['reviewReply']['updateTime'])
            ? Carbon::parse($data['reviewReply']['updateTime'])->setTimezone('Asia/Tokyo')
            : null;

        $review = GoogleReview::updateOrCreate(
            ['google_review_id' => $reviewId],
            [
                'store_id' => $store->id,
                'reviewer_name' => $reviewerName,
                'reviewer_photo_url' => $reviewerPhoto,
                'rating' => $rating,
                'comment' => $comment,
                'reply_comment' => $replyComment,
                'replied_at' => $repliedAt,
                'reviewed_at' => $reviewedAt,
            ]
        );

        // 新規の低評価口コミ（★1-2）の場合、通知メール送信
        if ($isNew && $rating <= 2 && $store->notify_email) {
            try {
                Mail::to($store->notify_email)
                    ->send(new \App\Mail\GoogleLowRatingNotification($review, $store));
            } catch (\Exception $e) {
                Log::error('Google低評価通知メール送信失敗', [
                    'store' => $store->name,
                    'review' => $review->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $review;
    }

    /**
     * 口コミに返信を投稿
     */
    public function replyToReview(GoogleReview $review, string $comment): bool
    {
        $accountName = SiteSetting::get('google_account_id');
        $store = $review->store;
        $locationName = $store->google_location_name;

        if (!$accountName || !$locationName) {
            return false;
        }

        $url = $this->apiBase . "/{$accountName}/{$locationName}/reviews/{$review->google_review_id}/reply";

        $response = $this->apiPut($url, [
            'comment' => $comment,
        ]);

        if (!$response || !$response->successful()) {
            Log::error('Google API: replyToReview failed', [
                'review' => $review->id,
                'body' => $response?->body(),
            ]);
            return false;
        }

        $review->update([
            'reply_comment' => $comment,
            'replied_at' => now(),
        ]);

        return true;
    }

    /**
     * 口コミの返信を削除
     */
    public function deleteReply(GoogleReview $review): bool
    {
        $accountName = SiteSetting::get('google_account_id');
        $store = $review->store;
        $locationName = $store->google_location_name;

        if (!$accountName || !$locationName) {
            return false;
        }

        $url = $this->apiBase . "/{$accountName}/{$locationName}/reviews/{$review->google_review_id}/reply";

        $response = $this->apiDelete($url);

        if (!$response || !$response->successful()) {
            Log::error('Google API: deleteReply failed', [
                'review' => $review->id,
                'body' => $response?->body(),
            ]);
            return false;
        }

        $review->update([
            'reply_comment' => null,
            'replied_at' => null,
        ]);

        return true;
    }

    // ==========================================
    // Google ビジネス投稿（Local Post）機能
    // ==========================================

    /**
     * ローカル投稿を作成（最新情報タイプ）
     */
    public function createLocalPost(Store $store, string $summary, ?string $imageUrl = null, ?string $actionUrl = null): array
    {
        $accountName = SiteSetting::get('google_account_id');
        $locationName = $store->google_location_name;

        if (!$accountName || !$locationName) {
            return ['success' => false, 'error' => 'Google Business設定が不完全です（アカウントIDまたはロケーション未設定）'];
        }

        $url = $this->apiBase . "/{$accountName}/{$locationName}/localPosts";

        $postData = [
            'languageCode' => 'ja',
            'summary' => $summary,
            'topicType' => 'STANDARD',
        ];

        if ($actionUrl) {
            $postData['callToAction'] = [
                'actionType' => 'LEARN_MORE',
                'url' => $actionUrl,
            ];
        }

        if ($imageUrl) {
            $postData['media'] = [
                [
                    'mediaFormat' => 'PHOTO',
                    'sourceUrl' => $imageUrl,
                ],
            ];
        }

        $response = $this->apiPost($url, $postData);

        if (!$response) {
            return ['success' => false, 'error' => 'APIトークンが取得できませんでした'];
        }

        if (!$response->successful()) {
            Log::error('Google API: createLocalPost failed', [
                'store' => $store->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['success' => false, 'error' => 'Google API エラー: HTTP ' . $response->status() . ' - ' . $response->body()];
        }

        $data = $response->json();
        return [
            'success' => true,
            'post_name' => $data['name'] ?? null,
        ];
    }

    /**
     * ローカル投稿を削除
     */
    public function deleteLocalPost(string $postName): bool
    {
        $response = $this->apiDelete($this->apiBase . '/' . ltrim($postName, '/'));

        if (!$response || !$response->successful()) {
            Log::error('Google API: deleteLocalPost failed', [
                'postName' => $postName,
                'body' => $response?->body(),
            ]);
            return false;
        }

        return true;
    }

    // ==========================================
    // Google ビジネス商品（Local Post PRODUCT タイプ）
    // ==========================================

    /**
     * 商品をPRODUCTタイプのローカル投稿として作成
     * ※ GBPの「商品」タブにはパブリックAPIがないため、localPostsのPRODUCTタイプで代替
     */
    public function createProduct(Store $store, array $productData): array
    {
        $accountName = SiteSetting::get('google_account_id');
        $locationName = $store->google_location_name;

        if (!$accountName || !$locationName) {
            return ['success' => false, 'error' => 'Google Business設定が不完全です'];
        }

        $url = $this->apiBase . "/{$accountName}/{$locationName}/localPosts";

        $postPayload = [
            'languageCode' => 'ja',
            'topicType' => 'STANDARD',
            'summary' => $productData['name'] . "\n\n" . ($productData['description'] ?? ''),
        ];

        if (!empty($productData['url'])) {
            $postPayload['callToAction'] = [
                'actionType' => 'LEARN_MORE',
                'url' => $productData['url'],
            ];
        }

        if (!empty($productData['image_url'])) {
            $postPayload['media'] = [
                [
                    'mediaFormat' => 'PHOTO',
                    'sourceUrl' => $productData['image_url'],
                ],
            ];
        }

        $response = $this->apiPost($url, $postPayload);

        if (!$response) {
            return ['success' => false, 'error' => 'APIトークンが取得できませんでした'];
        }

        if (!$response->successful()) {
            Log::error('Google API: createProduct (localPost) failed', [
                'store' => $store->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['success' => false, 'error' => 'Google API エラー: HTTP ' . $response->status() . ' - ' . $response->body()];
        }

        $data = $response->json();
        return [
            'success' => true,
            'product_name' => $data['name'] ?? null,
        ];
    }

    /**
     * 商品を削除
     */
    public function deleteProduct(string $productName): bool
    {
        $response = $this->apiDelete($this->apiBase . '/' . ltrim($productName, '/'));

        if (!$response || !$response->successful()) {
            Log::error('Google API: deleteProduct failed', [
                'productName' => $productName,
                'body' => $response?->body(),
            ]);
            return false;
        }

        return true;
    }
}
