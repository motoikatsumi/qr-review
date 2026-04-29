<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\SuggestionCategory;
use App\Services\GeminiService;
use App\Mail\LowRatingNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    private const MAX_IMAGES = 5;
    private const MAX_IMAGE_SIZE_KB = 5120; // 5MB

    /**
     * レビューフォームを表示
     */
    public function show($slug)
    {
        $store = Store::with('businessType')->where('slug', $slug)->where('is_active', true)->firstOrFail();

        // 投稿済みの場合はサンキューページへ
        if (session('review_submitted_' . $slug)) {
            return redirect('/review/' . $slug . '/thankyou');
        }

        // 業種が設定されている場合はその業種のカテゴリだけ表示、未設定なら全カテゴリ
        $suggestionQuery = SuggestionCategory::where('is_active', true)
            ->with(['activeThemes'])
            ->orderBy('sort_order');

        if ($store->business_type_id) {
            $suggestionQuery->where(function ($q) use ($store) {
                $q->where('business_type_id', $store->business_type_id)
                  ->orWhereNull('business_type_id'); // 共通カテゴリも表示
            });
        }

        $suggestionCategories = $suggestionQuery->get();

        $themeDisplayCount = (int) SiteSetting::get('suggestion_display_count', '6');

        // 業種から動的にレビュー質問グループを取得
        // 未設定時はデフォルト（性別・来店・年代・品目）を返す
        $reviewGroups = $store->businessType
            ? $store->businessType->activeReviewOptionGroups()
            : $this->defaultReviewGroups();

        // 修正で戻った場合：実存する画像のみを {filename, url} の配列で渡す
        $existingImages = [];
        foreach ($this->getValidUploadedImages($slug, (array) old('uploaded_images', [])) as $filename) {
            $existingImages[] = [
                'filename' => $filename,
                'url'      => $this->publicStorageUrl($this->uploadDir($slug) . '/' . $filename),
            ];
        }

        return view('review.form', compact('store', 'suggestionCategories', 'themeDisplayCount', 'reviewGroups', 'existingImages'));
    }

    /**
     * 業種未設定時のフォールバック用デフォルト質問グループ
     */
    private function defaultReviewGroups(): array
    {
        return [
            ['key' => 'gender',     'label' => '性別', 'options' => ['男性', '女性'], 'enabled' => true],
            ['key' => 'visit_type', 'label' => '来店', 'options' => ['新規', 'リピーター'], 'enabled' => true],
            ['key' => 'age',        'label' => '年代', 'options' => ['20代', '30代', '40代', '50代', '60代~'], 'enabled' => true],
        ];
    }

    /**
     * サンキューページを表示
     */
    public function thankyou($slug)
    {
        $store = Store::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('review.thankyou', compact('store'));
    }

    /**
     * 確認画面を表示（または修正のためフォームに戻る）
     */
    public function confirm(Request $request, $slug)
    {
        $store = Store::with('businessType')->where('slug', $slug)->where('is_active', true)->firstOrFail();

        // 投稿済みの場合はサンキューページへ
        if (session('review_submitted_' . $slug)) {
            return redirect('/review/' . $slug . '/thankyou');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:2000',
            'is_ai_generated' => 'nullable|boolean',
        ]);

        // 動的質問グループの回答を persona 配列に集約
        $reviewGroups = $store->businessType
            ? $store->businessType->activeReviewOptionGroups()
            : $this->defaultReviewGroups();
        $persona = $this->collectPersonaInput($request, $reviewGroups);

        // アップロード済み画像（ファイル名のみ受け取り、実存するものだけ採用）
        $uploadedImages = $this->getValidUploadedImages($slug, (array) $request->input('uploaded_images', []));

        // 「修正する」ボタンからの戻り
        if ($request->has('_back')) {
            $allKeys = array_merge(['rating', 'comment', 'is_ai_generated', 'uploaded_images'], array_column($reviewGroups, 'key'));
            return redirect('/review/' . $slug)->withInput($request->only($allKeys));
        }

        // 確認画面用の二重送信防止トークン
        $submitToken = Str::random(40);
        session(['review_submit_token' => $submitToken]);

        return view('review.confirm', [
            'store' => $store,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'is_ai_generated' => $validated['is_ai_generated'] ?? 0,
            'reviewGroups' => $reviewGroups,
            'persona' => $persona,
            'submitToken' => $submitToken,
            'uploadedImages' => $uploadedImages,
            'uploadedImageUrls' => array_map(fn($f) => $this->publicStorageUrl($this->uploadDir($slug) . '/' . $f), $uploadedImages),
        ]);
    }

    /**
     * Request から reviewGroups に定義されたキーの値を収集して persona 配列にする。
     * allow_other_input=true のグループは「その他」+ <key>_other で自由入力を受け付け、
     * 自由入力が空ならそのグループは persona に格納しない（AI が品目に言及しないフォールバック）。
     */
    private function collectPersonaInput(Request $request, array $reviewGroups): array
    {
        $persona = [];
        foreach ($reviewGroups as $g) {
            $key = $g['key'] ?? '';
            if (!$key) continue;
            $value = $request->input($key);
            if (!is_string($value) || $value === '') continue;

            $allowOther = !empty($g['allow_other_input']);

            // 「その他」が選ばれた場合は自由入力欄の値を採用
            if ($allowOther && $value === 'その他') {
                $other = $request->input($key . '_other');
                if (is_string($other)) {
                    // 半角・全角空白の両方を除去
                    $other = preg_replace('/^[\s\x{3000}]+|[\s\x{3000}]+$/u', '', $other);
                    if ($other !== '') {
                        $persona[$key] = mb_substr($other, 0, 50);
                    }
                }
                // 空欄なら何も格納しない（AI への品目情報はオフ）
                continue;
            }

            // 通常の選択肢チェック（不正値は無視）
            if (!empty($g['options']) && !in_array($value, $g['options'], true)) {
                continue;
            }
            $persona[$key] = mb_substr($value, 0, 50);
        }
        return $persona;
    }

    /**
     * レビューを送信・処理
     */
    public function store(Request $request, $slug)
    {
        $store = Store::with('businessType')->where('slug', $slug)->where('is_active', true)->firstOrFail();

        // 投稿済みの場合はサンキューページへ
        if (session('review_submitted_' . $slug)) {
            return view('review.thankyou', compact('store'));
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:2000',
            'is_ai_generated' => 'nullable|boolean',
            'submit_token' => 'required|string',
        ]);

        // 動的質問グループ → persona
        $reviewGroups = $store->businessType
            ? $store->businessType->activeReviewOptionGroups()
            : $this->defaultReviewGroups();
        $persona = $this->collectPersonaInput($request, $reviewGroups);

        // Google口コミポリシー違反チェック（二重送信防止トークン消費前に実行）
        $gemini = new GeminiService();
        $policyError = $gemini->validateGooglePolicy($validated['comment'], $store);
        
        if ($policyError) {
            return back()
                ->withInput()
                ->withErrors(['comment' => $policyError]);
        }

        // 二重送信防止チェック（ポリシーチェック通過後にトークンを消費）
        $sessionToken = session()->pull('review_submit_token');
        if (!$sessionToken || $sessionToken !== $validated['submit_token']) {
            $this->cleanupUploadDir($slug);
            return view('review.thankyou', compact('store'));
        }

        // 高評価（閾値より上）→ DB保存 + Googleマップ誘導（confirmページで別タブ遷移済み）
        $threshold = $store->notify_threshold ?? 3;
        if ($validated['rating'] > $threshold) {
            $review = Review::create([
                'store_id' => $store->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
                'ai_generated_text' => !empty($validated['is_ai_generated']) ? $validated['comment'] : null,
                'status' => 'redirected_to_google',
                // 既存の 4 カラムとも後方互換で書き込み（ダッシュボード表示用）
                'gender'     => $persona['gender'] ?? null,
                'age'        => $persona['age'] ?? null,
                'visit_type' => $persona['visit_type'] ?? null,
                'item'       => $persona['item'] ?? null,
                'persona'    => $persona,
            ]);

            session(['review_submitted_' . $slug => true]);
            $this->cleanupUploadDir($slug);
            return view('review.thankyou', compact('store'));
        }

        // 低評価（閾値以下）→ DB保存 → メール送信
        $review = Review::create([
            'store_id' => $store->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'ai_generated_text' => !empty($validated['is_ai_generated']) ? $validated['comment'] : null,
            'status' => 'email_sent',
            'gender'     => $persona['gender'] ?? null,
            'age'        => $persona['age'] ?? null,
            'visit_type' => $persona['visit_type'] ?? null,
            'item'       => $persona['item'] ?? null,
            'persona'    => $persona,
        ]);

        // メール送信
        try {
            Mail::to($store->notify_email)->send(new LowRatingNotification($review, $store));
        } catch (\Exception $e) {
            \Log::error('メール送信エラー: ' . $e->getMessage());
        }

        session(['review_submitted_' . $slug => true]);
        $this->cleanupUploadDir($slug);
        return view('review.thankyou', compact('store'));
    }

    /**
     * 口コミ提案文をAIで生成（Ajax）
     */
    public function suggest(Request $request, $slug)
    {
        $store = Store::with('businessType')->where('slug', $slug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'keywords' => 'required|array|min:1',
            'keywords.*' => 'required|string|max:100',
        ]);

        // 動的な persona を収集
        $reviewGroups = $store->businessType
            ? $store->businessType->activeReviewOptionGroups()
            : $this->defaultReviewGroups();
        $persona = $this->collectPersonaInput($request, $reviewGroups);

        // アップロード済み画像があれば Gemini Vision に渡す（絶対パス）
        $imagePaths = [];
        $uploadedImages = $this->getValidUploadedImages($slug, (array) $request->input('uploaded_images', []));
        foreach ($uploadedImages as $filename) {
            $imagePaths[] = Storage::disk('public')->path($this->uploadDir($slug) . '/' . $filename);
        }

        $gemini = new GeminiService();
        $text = $gemini->generateSuggestion(
            $store,
            $validated['keywords'],
            $persona['gender']     ?? '',
            $persona['age']        ?? '',
            $persona['visit_type'] ?? '',
            $persona['item']       ?? '',
            $persona,  // 全 persona（カスタム質問対応）
            $imagePaths
        );

        if (!$text) {
            return response()->json(['error' => '文章の生成に失敗しました。'], 500);
        }

        return response()->json(['text' => trim($text)]);
    }

    /**
     * 画像アップロード（Ajax / 1枚ずつ）
     */
    public function uploadImage(Request $request, $slug)
    {
        Store::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,webp,heic,heif|max:' . self::MAX_IMAGE_SIZE_KB,
        ], [
            'image.mimes' => '対応していない画像形式です。(jpg / png / webp / heic)',
            'image.max'   => '画像サイズが大きすぎます。1枚あたり5MBまでです。',
        ]);

        $dir = $this->uploadDir($slug);

        // 既存枚数チェック
        $existing = Storage::disk('public')->files($dir);
        if (count($existing) >= self::MAX_IMAGES) {
            return response()->json(['error' => '画像は最大' . self::MAX_IMAGES . '枚までアップロードできます。'], 422);
        }

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $filename = Str::random(24) . '.' . $ext;
        $file->storeAs($dir, $filename, 'public');

        return response()->json([
            'filename' => $filename,
            'url'      => $this->publicStorageUrl($dir . '/' . $filename),
        ]);
    }

    /**
     * 画像削除（Ajax）
     */
    public function deleteImage(Request $request, $slug)
    {
        Store::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $request->validate([
            'filename' => 'required|string|max:255',
        ]);

        $filename = basename($request->input('filename')); // パストラバーサル対策
        $path = $this->uploadDir($slug) . '/' . $filename;

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * セッション × 店舗 ごとに分離されたアップロード保存先
     */
    private function uploadDir(string $slug): string
    {
        return 'review-uploads/' . md5(session()->getId() . '|' . $slug);
    }

    /**
     * 現在のリクエストのベースURL基準で storage/ 以下のパブリックURLを返す
     * （APP_URL がローカル/本番でズレても正しく解決できる）
     */
    private function publicStorageUrl(string $relativePath): string
    {
        return request()->getBaseUrl() . '/storage/' . ltrim($relativePath, '/');
    }

    /**
     * リクエストで渡されたファイル名のうち、実際にディレクトリに存在するものだけを返す
     */
    private function getValidUploadedImages(string $slug, array $filenames): array
    {
        $dir = $this->uploadDir($slug);
        $valid = [];
        foreach ($filenames as $f) {
            if (!is_string($f) || $f === '') continue;
            $f = basename($f);
            if (Storage::disk('public')->exists($dir . '/' . $f)) {
                $valid[] = $f;
            }
            if (count($valid) >= self::MAX_IMAGES) break;
        }
        return $valid;
    }

    /**
     * セッション専用アップロードディレクトリをまるごと削除
     */
    private function cleanupUploadDir(string $slug): void
    {
        $dir = $this->uploadDir($slug);
        if (Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->deleteDirectory($dir);
        }
    }
}
