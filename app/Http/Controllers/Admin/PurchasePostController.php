<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchasePost;
use App\Models\Store;
use App\Services\GeminiService;
use App\Services\GoogleBusinessService;
use App\Services\FacebookService;
use App\Services\InstagramService;
use App\Services\PawnSystemService;
use App\Services\WordPressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurchasePostController extends Controller
{
    /**
     * 店舗の業種設定からカテゴリマッピングを取得
     */
    protected function getCategoryMap(Store $store): array
    {
        $bt = $store->businessType;
        $categories = $bt->post_categories ?? [];
        $map = [];
        foreach ($categories as $cat) {
            $map[$cat['name']] = [
                'slug' => $cat['wp_slug'] ?? 'blog',
                'path' => $cat['wp_path'] ?? '',
            ];
        }
        return $map;
    }

    /**
     * 投稿タイトルを業種テンプレートから生成
     */
    protected function buildPublishTitle(PurchasePost $post): string
    {
        $store = $post->store;
        $bt = $store?->businessType;
        $actionWord = $bt->post_action_word ?? 'ご紹介';

        return $post->brand_name . ' ' . $post->product_name . ' ' . $post->product_status . 'を' . $actionWord . 'いたしました';
    }

    /**
     * 投稿一覧
     */
    public function index(Request $request)
    {
        $showTrashed = $request->input('show') === 'trashed';
        $query = ($showTrashed ? PurchasePost::onlyTrashed() : PurchasePost::query())
            ->with('store')->orderBy('created_at', 'desc');

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'published':
                    $query->where('wp_status', 'published')
                          ->where('google_post_status', 'published');
                    break;
                case 'partial':
                    $query->where(function ($q) {
                        $q->where('wp_status', '!=', 'published')
                          ->orWhere('google_post_status', '!=', 'published');
                    })->where(function ($q) {
                        $q->where('wp_status', 'published')
                          ->orWhere('google_post_status', 'published');
                    });
                    break;
                case 'failed':
                    $query->where(function ($q) {
                        $q->where('wp_status', 'failed')
                          ->orWhere('google_post_status', 'failed');
                    });
                    break;
            }
        }

        $posts = $query->paginate(30);
        $stores = Store::where('is_active', true)->orderBy('name')->get();
        $wpUrl = config('services.wordpress.url');

        // SNS プロフィールURL(列ヘッダーリンク用)。SiteSetting > 推測の優先順。
        $facebookUrl = \App\Models\SiteSetting::get('facebook_page_url');
        if (!$facebookUrl) {
            $fbPageId = \App\Models\SiteSetting::get('facebook_page_id');
            if ($fbPageId) $facebookUrl = 'https://www.facebook.com/profile.php?id=' . $fbPageId;
        }
        $instagramUrl = \App\Models\SiteSetting::get('instagram_account_url');

        $trashedCount = PurchasePost::onlyTrashed()->count();

        return view('admin.purchase-posts.index', compact('posts', 'stores', 'wpUrl', 'facebookUrl', 'instagramUrl', 'showTrashed', 'trashedCount'));
    }

    /**
     * 新規作成フォーム
     */
    public function create()
    {
        $stores = Store::with(['postTemplate', 'businessType'])->where('is_active', true)->orderBy('name')->get();

        return view('admin.purchase-posts.create', compact('stores'));
    }

    /**
     * pawn-systemから管理番号で在庫情報を取得（AJAX）
     */
    public function fetchStock(Request $request)
    {
        $request->validate([
            'manage_number' => 'required|string|max:50',
        ]);

        $service = new PawnSystemService();
        $data = $service->getStockByManageNumber($request->manage_number);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => '在庫が見つかりません。管理番号を確認してください。',
            ]);
        }

        // AIで性別を判定
        $gemini = new GeminiService();

        if (!empty($data['customer_name'])) {
            try {
                $gender = $gemini->estimateGender($data['customer_name']);
                $data['customer_gender'] = $gender;
            } catch (\Exception $e) {
                Log::warning('性別AI判定エラー', ['message' => $e->getMessage()]);
                $data['customer_gender'] = null;
            }
        } else {
            $data['customer_gender'] = null;
        }

        // AIでブランド名・商品名（型番込み）を抽出
        if (!empty($data['feature'])) {
            try {
                $productInfo = $gemini->extractProductInfo($data['feature']);
                if ($productInfo) {
                    if (!empty($productInfo['product_name'])) {
                        $data['product_name'] = $productInfo['product_name'];
                    }
                    if (!empty($productInfo['brand_name'])) {
                        $data['brand_name'] = $productInfo['brand_name'];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('商品情報AI抽出エラー', ['message' => $e->getMessage()]);
            }
        }

        // featureから商品の状態・付属品を抽出
        $data['detected_conditions'] = [];
        $data['detected_accessories'] = [];
        if (!empty($data['feature'])) {
            $data['detected_conditions'] = $this->detectConditions($data['feature']);
            $data['detected_accessories'] = $this->detectAccessories($data['feature']);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * featureテキストから商品の状態に該当するキーワードを抽出
     */
    private function detectConditions(string $feature): array
    {
        $conditionKeywords = [
            '目立つキズなし' => ['キズなし', '傷なし', 'きずなし', '目立つキズなし', '目立つ傷なし', '目立った傷なし'],
            '全体的に良好'  => ['良好', '美品', '良品', '綺麗', 'きれい'],
            '多少の使用感あり' => ['使用感', '使用感あり', 'スレ', 'スレあり'],
            '未使用品'      => ['未使用', '未使用品'],
            '新品同様'      => ['新品同様', 'ほぼ新品'],
            '小キズあり'    => ['小キズ', '小傷', 'コキズ', '薄キズ', '薄傷', '微細なキズ'],
            '汚れあり'      => ['汚れ', '汚れあり', 'シミ', '変色', 'くすみ'],
            '動作確認済み'  => ['動作確認済', '動作OK', '動作良好', '稼働確認'],
        ];

        $detected = [];
        $featureLower = mb_strtolower($feature, 'UTF-8');

        foreach ($conditionKeywords as $label => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($featureLower, mb_strtolower($keyword, 'UTF-8')) !== false) {
                    $detected[] = $label;
                    break;
                }
            }
        }

        return array_unique($detected);
    }

    /**
     * featureテキストから付属品に該当するキーワードを抽出
     */
    private function detectAccessories(string $feature): array
    {
        $accessoryKeywords = [
            '箱'             => ['箱', 'BOX', 'ボックス'],
            '保証書'          => ['保証書', '保証カード', 'ギャランティ', 'WARRANTY'],
            '説明書'          => ['説明書', 'マニュアル'],
            '替えベルト'       => ['替えベルト', '予備ベルト', '替えバンド'],
            '充電器'          => ['充電器', 'チャージャー', 'ACアダプタ', '充電ケーブル'],
            'ケース'          => ['ケース付', '専用ケース', 'ハードケース', 'ソフトケース'],
            '袋'             => ['袋', '保存袋', 'ポーチ'],
            'ギャランティカード' => ['ギャランティ', 'ギャラ'],
            '鑑定書'          => ['鑑定書', '鑑別書'],
            'コマ'            => ['コマ', '余りコマ', '駒'],
        ];

        $detected = [];

        foreach ($accessoryKeywords as $label => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_stripos($feature, $keyword) !== false) {
                    $detected[] = $label;
                    break;
                }
            }
        }

        // 「完備付属」「完備」は全付属品の意味合い
        if (mb_strpos($feature, '完備') !== false) {
            if (!in_array('箱', $detected)) $detected[] = '箱';
            if (!in_array('保証書', $detected)) $detected[] = '保証書';
        }

        return array_unique($detected);
    }

    /**
     * AI でブロック②を生成（AJAX）
     */
    public function generateEpisode(Request $request)
    {
        // 業種によってbrand/productが不要な場合がある
        $hiddenFields = [];
        if ($request->filled('store_id')) {
            $store = \App\Models\Store::with('businessType')->find($request->store_id);
            $hiddenFields = $store->businessType->post_hidden_fields ?? [];
        }

        $brandRule = in_array('brand_name', $hiddenFields) ? 'nullable|string|max:200' : 'required|string|max:200';
        $productRule = in_array('product_name', $hiddenFields) ? 'nullable|string|max:200' : 'required|string|max:200';

        $request->validate([
            'brand_name' => $brandRule,
            'product_name' => $productRule,
        ]);

        $params = $request->only([
            'brand_name', 'product_name', 'customer_gender', 'customer_age',
            'customer_reason', 'product_condition', 'accessories', 'category',
        ]);

        $store = null;
        if ($request->filled('store_id')) {
            $store = \App\Models\Store::find($request->store_id);
        }

        // フォームデータのエンコーディングを修正
        foreach ($params as $key => $value) {
            if (is_string($value) && $value !== '') {
                // 不正なUTF-8バイトを除去
                $params[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
        }

        $gemini = new GeminiService();
        $text = $gemini->generatePurchaseEpisode($params, $store);

        if ($text) {
            return response()->json(['success' => true, 'text' => $text]);
        }

        return response()->json(['success' => false, 'error' => 'AI生成に失敗しました。再度お試しください。'], 500);
    }

    /**
     * ブロック③テンプレートをAI生成（AJAX）
     */
    public function generateFooter(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'area' => 'required|string|max:200',
        ]);

        $store = Store::findOrFail($request->store_id);
        $gemini = new GeminiService();
        $text = $gemini->generateStoreFooterTemplate($store, $request->area);

        if ($text) {
            return response()->json(['success' => true, 'text' => $text]);
        }

        return response()->json(['success' => false, 'error' => 'AI生成に失敗しました。'], 500);
    }

    /**
     * 投稿を保存＆公開
     */
    public function store(Request $request)
    {
        $store = Store::with('businessType')->findOrFail($request->store_id);
        $categoryMap = $this->getCategoryMap($store);
        $hiddenFields = $store->businessType->post_hidden_fields ?? [];

        // brand_name/product_name は業種で非表示の場合はnullable
        $brandRule = in_array('brand_name', $hiddenFields) ? 'nullable|string|max:200' : 'required|string|max:200';
        $productRule = in_array('product_name', $hiddenFields) ? 'nullable|string|max:200' : 'required|string|max:200';

        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'brand_name' => $brandRule,
            'product_name' => $productRule,
            'product_status' => 'required|string|max:50',
            'category' => ['required', 'string', 'max:50'],
            'block1_text' => 'required|string',
            'block2_text' => 'required|string',
            'block3_text' => 'required|string',
            'image' => 'required|image|min:11|max:10240|dimensions:min_width=250,min_height=250',
            'wp_tag_name' => 'nullable|string|max:100',
            'custom_hashtags' => 'nullable|string|max:2000',
            'rank' => 'nullable|string|in:S,A,B,C,D',
        ], [
            'image.min' => '画像ファイルサイズが小さすぎます（最低11KB必要です。Google APIの要件: 10KB以上）。',
        ]);

        // 重複チェック（brand/productがある業種のみ）
        if (!in_array('brand_name', $hiddenFields) && $request->brand_name && $request->product_name) {
            $duplicate = PurchasePost::where('store_id', $request->store_id)
                ->where('brand_name', $request->brand_name)
                ->where('product_name', $request->product_name)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if ($duplicate) {
                return back()->withInput()->with('error', '同じ店舗・ブランド名・商品名の投稿が24時間以内に存在します。重複投稿でないか確認してください。');
            }
        }

        // 画像をローカルに一時保存
        $imagePath = $request->file('image')->store('purchase-posts', 'public');
        $fullImagePath = storage_path('app/public/' . $imagePath);

        // 全文組み立て（○○をカテゴリ名に置換）
        $block3Replaced = str_replace('○○', $request->category, $request->block3_text);
        $fullText = $request->block1_text . "\n\n" . $request->block2_text . "\n\n" . $block3Replaced;

        // カテゴリマッピング（業種設定から取得）
        $catInfo = $categoryMap[$request->category] ?? ['slug' => 'blog', 'path' => ''];

        $post = PurchasePost::create([
            'store_id' => $store->id,
            'brand_name' => $request->brand_name,
            'product_name' => $request->product_name,
            'product_status' => $request->product_status,
            'rank' => $request->rank,
            'category' => $request->category,
            'customer_gender' => $request->customer_gender,
            'customer_age' => $request->customer_age,
            'customer_reason' => $request->customer_reason,
            'product_condition' => $request->product_condition,
            'accessories' => $request->accessories,
            'block1_text' => $request->block1_text,
            'block2_text' => $request->block2_text,
            'block3_text' => $request->block3_text,
            'full_text' => $fullText,
            'image_path' => $imagePath,
            'wp_category_slug' => $catInfo['slug'],
            'wp_tag_name' => $request->wp_tag_name,
            'custom_hashtags' => $request->custom_hashtags,
        ]);

        // --- WordPress 投稿 ---
        $this->publishToWordPress($post, $fullImagePath);

        // --- Google ビジネス投稿（最新情報） ---
        $this->publishToGooglePost($post, $catInfo);

        // --- Google ビジネス写真ギャラリー ---
        $this->publishToGooglePhoto($post);

        // --- Instagram 投稿 ---
        $this->publishToInstagram($post);

        // --- Facebook 投稿 ---
        $this->publishToFacebook($post);

        $post->published_at = now();
        $post->save();

        $messages = [];
        if ($post->wp_status === 'published') $messages[] = 'WordPress投稿完了';
        if ($post->wp_status === 'failed') $messages[] = 'WordPress投稿失敗: ' . $post->wp_error;
        if ($post->google_post_status === 'published') $messages[] = 'Google投稿完了';
        if ($post->google_post_status === 'failed') $messages[] = 'Google投稿失敗: ' . $post->google_post_error;
        if ($post->google_photo_status === 'published') $messages[] = 'Google写真追加完了';
        if ($post->google_photo_status === 'failed') $messages[] = 'Google写真追加失敗: ' . $post->google_photo_error;
        if ($post->instagram_status === 'published') $messages[] = 'Instagram投稿完了';
        if ($post->instagram_status === 'failed') $messages[] = 'Instagram投稿失敗: ' . $post->instagram_error;
        if ($post->facebook_status === 'published') $messages[] = 'Facebook投稿完了';
        if ($post->facebook_status === 'failed') $messages[] = 'Facebook投稿失敗: ' . $post->facebook_error;

        $hasError = $post->wp_status === 'failed' || $post->google_post_status === 'failed' || $post->google_photo_status === 'failed' || $post->instagram_status === 'failed' || $post->facebook_status === 'failed';

        return redirect()->route('admin.purchase-posts.index')
            ->with($hasError ? 'error' : 'success', implode(' / ', $messages));
    }

    /**
     * 投稿詳細
     */
    public function show(PurchasePost $purchasePost)
    {
        $purchasePost->load('store');
        return view('admin.purchase-posts.show', compact('purchasePost'));
    }

    /**
     * 編集フォーム
     */
    public function edit(PurchasePost $purchasePost)
    {
        $stores = Store::with(['postTemplate', 'businessType'])->where('is_active', true)->orderBy('name')->get();

        return view('admin.purchase-posts.edit', compact('purchasePost', 'stores'));
    }

    /**
     * 投稿を更新（ローカルデータのみ）
     */
    public function update(Request $request, PurchasePost $purchasePost)
    {
        $store = Store::with('businessType')->findOrFail($request->store_id);

        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'brand_name' => 'required|string|max:200',
            'product_name' => 'required|string|max:200',
            'product_status' => 'required|string|max:50',
            'category' => ['required', 'string', 'max:50'],
            'block1_text' => 'required|string',
            'block2_text' => 'required|string',
            'block3_text' => 'required|string',
            'image' => 'nullable|image|min:11|max:10240|dimensions:min_width=250,min_height=250',
            'wp_tag_name' => 'nullable|string|max:100',
            'custom_hashtags' => 'nullable|string|max:2000',
            'rank' => 'nullable|string|in:S,A,B,C,D',
        ]);

        $categoryMap = $this->getCategoryMap($store);
        $catInfo = $categoryMap[$request->category] ?? ['slug' => 'blog', 'path' => ''];
        $block3Replaced = str_replace('○○', $request->category, $request->block3_text);
        $fullText = $request->block1_text . "\n\n" . $request->block2_text . "\n\n" . $block3Replaced;

        $updateData = [
            'store_id' => $request->store_id,
            'brand_name' => $request->brand_name,
            'product_name' => $request->product_name,
            'product_status' => $request->product_status,
            'rank' => $request->rank,
            'category' => $request->category,
            'block1_text' => $request->block1_text,
            'block2_text' => $request->block2_text,
            'block3_text' => $request->block3_text,
            'full_text' => $fullText,
            'wp_category_slug' => $catInfo['slug'],
            'wp_tag_name' => $request->wp_tag_name,
            'custom_hashtags' => $request->custom_hashtags,
        ];

        // 画像が新しくアップロードされた場合
        if ($request->hasFile('image')) {
            // 旧画像を削除
            if ($purchasePost->image_path) {
                Storage::disk('public')->delete($purchasePost->image_path);
            }
            $updateData['image_path'] = $request->file('image')->store('purchase-posts', 'public');
        }

        $purchasePost->update($updateData);

        return redirect()->route('admin.purchase-posts.show', $purchasePost)
            ->with('success', '投稿を更新しました');
    }

    /**
     * リトライ（失敗した投稿先のみ再投稿）
     */
    public function retry(PurchasePost $purchasePost)
    {
        $purchasePost->load('store.businessType');
        $fullImagePath = storage_path('app/public/' . $purchasePost->image_path);
        $categoryMap = $purchasePost->store ? $this->getCategoryMap($purchasePost->store) : [];
        $catInfo = $categoryMap[$purchasePost->category] ?? ['slug' => 'blog', 'path' => ''];
        $messages = [];

        if ($purchasePost->wp_status === 'failed') {
            $this->publishToWordPress($purchasePost, $fullImagePath);
            $messages[] = $purchasePost->wp_status === 'published' ? 'WordPress再投稿成功' : 'WordPress再投稿失敗';
        }

        if ($purchasePost->google_post_status === 'failed') {
            $this->publishToGooglePost($purchasePost, $catInfo);
            $messages[] = $purchasePost->google_post_status === 'published' ? 'Google投稿再投稿成功' : 'Google投稿再投稿失敗';
        }

        if ($purchasePost->google_photo_status === 'failed') {
            $this->publishToGooglePhoto($purchasePost);
            $messages[] = $purchasePost->google_photo_status === 'published' ? 'Google写真再追加成功' : 'Google写真再追加失敗';
        }

        if ($purchasePost->instagram_status === 'failed') {
            $this->publishToInstagram($purchasePost);
            $messages[] = $purchasePost->instagram_status === 'published' ? 'Instagram再投稿成功' : 'Instagram再投稿失敗';
        }

        if ($purchasePost->facebook_status === 'failed') {
            $this->publishToFacebook($purchasePost);
            $messages[] = $purchasePost->facebook_status === 'published' ? 'Facebook再投稿成功' : 'Facebook再投稿失敗';
        }

        $purchasePost->save();

        $hasError = $purchasePost->wp_status === 'failed' || $purchasePost->google_post_status === 'failed' || $purchasePost->google_photo_status === 'failed' || $purchasePost->instagram_status === 'failed' || $purchasePost->facebook_status === 'failed';

        return redirect()->route('admin.purchase-posts.show', $purchasePost)
            ->with($hasError ? 'error' : 'success', implode(' / ', $messages));
    }

    /**
     * 投稿削除
     */
    public function destroy(PurchasePost $purchasePost)
    {
        // WordPress投稿を削除
        if ($purchasePost->wp_post_id) {
            try {
                $wp = WordPressService::forStore($purchasePost->store);
                $wp->deletePost($purchasePost->wp_post_id);
                if ($purchasePost->wp_media_id) {
                    $wp->deleteMedia($purchasePost->wp_media_id);
                }
            } catch (\Exception $e) {
                Log::error('WordPress delete failed', ['error' => $e->getMessage()]);
            }
        }

        // Google投稿を削除
        if ($purchasePost->google_post_id) {
            try {
                $google = new GoogleBusinessService();
                $google->deleteLocalPost($purchasePost->google_post_id);
            } catch (\Exception $e) {
                Log::error('Google post delete failed', ['error' => $e->getMessage()]);
            }
        }

        // Google写真を削除
        if ($purchasePost->google_photo_name) {
            try {
                $google = $google ?? new GoogleBusinessService();
                $google->deletePhoto($purchasePost->google_photo_name);
            } catch (\Exception $e) {
                Log::error('Google photo delete failed', ['error' => $e->getMessage()]);
            }
        }

        // Instagram投稿は削除APIが無いためスキップ（投稿は残る）

        // Facebook投稿を削除
        if ($purchasePost->facebook_post_id) {
            try {
                $fb = FacebookService::forStore($purchasePost->store);
                $fb->deletePost($purchasePost->facebook_post_id);
            } catch (\Exception $e) {
                Log::error('Facebook post delete failed', ['error' => $e->getMessage()]);
            }
        }

        // ローカル画像を削除
        if ($purchasePost->image_path) {
            Storage::disk('public')->delete($purchasePost->image_path);
        }

        $purchasePost->delete();

        return redirect()->route('admin.purchase-posts.index')
            ->with('success', '投稿を削除しました');
    }

    /**
     * 削除済み投稿を復元（ローカル DB レコードのみ。外部投稿は復活しない）
     */
    public function restore($id)
    {
        $post = PurchasePost::onlyTrashed()->findOrFail($id);
        $post->restore();
        return back()->with('success', "投稿「{$post->brand_name} {$post->product_name}」をゴミ箱から復元しました。（WordPress / SNS への再投稿は別途お願いします）");
    }

    /**
     * 完全削除（DB から物理削除）
     */
    public function forceDelete($id)
    {
        $post = PurchasePost::onlyTrashed()->findOrFail($id);

        // ローカル画像が残っていれば削除
        if ($post->image_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($post->image_path);
        }

        $label = "{$post->brand_name} {$post->product_name}";
        $post->forceDelete();
        return back()->with('success', "投稿「{$label}」を完全に削除しました。");
    }

    // ==============================
    // Private Publishing Methods
    // ==============================

    protected function publishToWordPress(PurchasePost $post, string $imagePath): void
    {
        try {
            $wp = WordPressService::forStore($post->store);

            // 画像アップロード
            $fileName = $post->brand_name . '_' . $post->product_name . '.' . pathinfo($imagePath, PATHINFO_EXTENSION);
            $media = $wp->uploadMedia($imagePath, $fileName);
            $post->wp_media_id = $media['id'];
            $post->wp_image_url = $media['url'];

            // カテゴリ取得
            $categoryIds = [];
            if ($post->wp_category_slug) {
                $catId = $wp->findCategoryBySlug($post->wp_category_slug);
                if ($catId) $categoryIds[] = $catId;
            }

            // タグ取得/作成
            $tagIds = [];
            if ($post->wp_tag_name) {
                $tagId = $wp->findOrCreateTag($post->wp_tag_name);
                if ($tagId) $tagIds[] = $tagId;
            }

            // WordPress投稿内容をHTML形式で組み立て
            $htmlContent = '<p>' . nl2br(e($post->full_text)) . '</p>';
            if ($post->wp_image_url) {
                $htmlContent = '<!-- wp:image {"id":' . $post->wp_media_id . '} -->'
                    . '<figure class="wp-block-image"><img src="' . e($post->wp_image_url) . '" alt="' . e($post->brand_name . ' ' . $post->product_name) . '" class="wp-image-' . $post->wp_media_id . '"/></figure>'
                    . '<!-- /wp:image -->'
                    . $htmlContent;
            }

            $title = $this->buildPublishTitle($post);

            $result = $wp->createPost([
                'title' => $title,
                'content' => $htmlContent,
                'categories' => $categoryIds,
                'tags' => $tagIds,
                'featured_media' => $media['id'],
                'meta' => [
                    'state' => $post->full_text,
                    'rank' => $post->rank ?? '',
                ],
            ]);

            if ($result) {
                $post->wp_post_id = $result['id'];
                $post->wp_status = 'published';
                $post->wp_error = null;
            } else {
                $post->wp_status = 'failed';
                $post->wp_error = 'WordPress投稿の作成に失敗しました';
            }
        } catch (\Exception $e) {
            $post->wp_status = 'failed';
            $post->wp_error = $e->getMessage();
            Log::error('WordPress publish failed', ['error' => $e->getMessage()]);
        }
    }

    protected function publishToGooglePost(PurchasePost $post, array $catInfo): void
    {
        try {
            $store = $post->store;
            if (!$store->google_location_name) {
                $post->google_post_status = 'failed';
                $post->google_post_error = 'Google Businessのロケーションが未設定です';
                return;
            }

            $google = new GoogleBusinessService();
            $imageUrl = $post->wp_image_url;
            $wpBaseUrl = rtrim(config('services.wordpress.url'), '/');
            $actionUrl = ($wpBaseUrl && !empty($catInfo['path'])) ? $wpBaseUrl . $catInfo['path'] : null;

            $result = $google->createLocalPost($store, $post->full_text, $imageUrl, $actionUrl ?: null);

            if ($result['success']) {
                $post->google_post_id = $result['post_name'];
                $post->google_post_status = 'published';
                $post->google_post_error = null;
            } else {
                $post->google_post_status = 'failed';
                $post->google_post_error = $result['error'];
            }
        } catch (\Exception $e) {
            $post->google_post_status = 'failed';
            $post->google_post_error = $e->getMessage();
            Log::error('Google post publish failed', ['error' => $e->getMessage()]);
        }
    }

    protected function publishToGooglePhoto(PurchasePost $post): void
    {
        try {
            $store = $post->store;
            if (!$store->google_location_name) {
                $post->google_photo_status = 'failed';
                $post->google_photo_error = 'Google Businessのロケーションが未設定です';
                return;
            }

            $imageUrl = $post->wp_image_url;
            if (!$imageUrl) {
                $post->google_photo_status = 'failed';
                $post->google_photo_error = 'WordPress画像URLが未設定です（WordPress投稿が先に必要）';
                return;
            }

            $google = new GoogleBusinessService();
            $result = $google->uploadPhoto($store, $imageUrl);

            if ($result['success']) {
                $post->google_photo_name = $result['media_name'];
                $post->google_photo_status = 'published';
                $post->google_photo_error = null;
            } else {
                $post->google_photo_status = 'failed';
                $post->google_photo_error = $result['error'];
            }
        } catch (\Exception $e) {
            $post->google_photo_status = 'failed';
            $post->google_photo_error = $e->getMessage();
            Log::error('Google photo upload failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 投稿用の画像 URL を決定する。
     * - 店舗が WordPress 連携を使う設定: 既存の wp_image_url を返す（後段で Cloudinary にミラーされる）
     * - WordPress 連携を使わない設定: image_path のローカルファイルを直接 Cloudinary にアップロード
     */
    protected function resolveImageUrl(PurchasePost $post): ?string
    {
        $useWp = $post->store->use_wordpress ?? true;

        if ($useWp && !empty($post->wp_image_url)) {
            return $post->wp_image_url;
        }

        // WordPress 連携 OFF または wp_image_url が無い → ローカル画像を直接 Cloudinary に
        if (empty($post->image_path)) {
            return null;
        }
        $localPath = storage_path('app/public/' . $post->image_path);
        if (!file_exists($localPath)) {
            return null;
        }

        $mirror = (new \App\Services\ImageMirrorService())->mirrorFromLocal($localPath);
        return $mirror;  // Cloudinary URL（失敗時は null）
    }

    protected function publishToInstagram(PurchasePost $post): void
    {
        try {
            $instagram = InstagramService::forStore($post->store);
            if (!$instagram->isConnected()) {
                $post->instagram_status = 'failed';
                $post->instagram_error = 'Instagram APIが未設定です';
                return;
            }

            $imageUrl = $this->resolveImageUrl($post);
            if (!$imageUrl) {
                $post->instagram_status = 'failed';
                $post->instagram_error = '画像が見つかりません（WordPress 連携 OFF の場合は image_path にローカルファイルが必要）';
                return;
            }

            // キャプション組み立て
            $hashtags = $this->generateHashtags($post);
            $caption = $this->buildPublishTitle($post) . "\n\n" . $post->full_text . "\n\n" . $hashtags;

            $result = $instagram->publishPost($imageUrl, $caption);

            if ($result['success']) {
                $post->instagram_media_id = $result['media_id'];
                $post->instagram_status = 'published';
                $post->instagram_error = null;
            } else {
                $post->instagram_status = 'failed';
                $post->instagram_error = $result['error'];
            }
        } catch (\Exception $e) {
            $post->instagram_status = 'failed';
            $post->instagram_error = $e->getMessage();
            Log::error('Instagram publish failed', ['error' => $e->getMessage()]);
        }
    }

    protected function publishToFacebook(PurchasePost $post): void
    {
        try {
            $facebook = FacebookService::forStore($post->store);
            if (!$facebook->isConnected()) {
                $post->facebook_status = 'failed';
                $post->facebook_error = 'Facebook APIが未設定です';
                return;
            }

            $imageUrl = $this->resolveImageUrl($post);
            if (!$imageUrl) {
                $post->facebook_status = 'failed';
                $post->facebook_error = '画像が見つかりません（WordPress 連携 OFF の場合は image_path にローカルファイルが必要）';
                return;
            }

            // メッセージ組み立て
            $hashtags = $this->generateHashtags($post);
            $message = $this->buildPublishTitle($post) . "\n\n" . $post->full_text . "\n\n" . $hashtags;

            $result = $facebook->publishPost($imageUrl, $message);

            if ($result['success']) {
                $post->facebook_post_id = $result['post_id'];
                $post->facebook_status = 'published';
                $post->facebook_error = null;
            } else {
                $post->facebook_status = 'failed';
                $post->facebook_error = $result['error'];
            }
        } catch (\Exception $e) {
            $post->facebook_status = 'failed';
            $post->facebook_error = $e->getMessage();
            Log::error('Facebook publish failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ハッシュタグを生成
     *
     * 構成:
     *   1. 投稿の custom_hashtags(店舗ハッシュタグ + 業種デフォルトの結合) ─ 静的部分
     *   2. カテゴリ + 「(カテゴリ)買取」 ─ 動的部分
     *   3. ブランド + 「(ブランド)買取」 ─ 動的部分
     *   4. 商品名(型番込み) ─ 動的部分
     * 1 だけでは投稿ごとに変わる動的要素が反映されないので、必ず 2〜4 も付与する。
     */
    protected function generateHashtags(PurchasePost $post): string
    {
        $tags = [];

        // 1. 投稿の custom_hashtags(店舗マスタ・業種マスタ由来の静的タグ)
        if (!empty($post->custom_hashtags)) {
            $lines = array_filter(array_map('trim', explode("\n", str_replace("\r\n", "\n", $post->custom_hashtags))));
            foreach ($lines as $line) {
                $tag = ltrim($line, '#');
                if ($tag) $tags[] = $tag;
            }
        }

        // 2. カテゴリ + 「(カテゴリ)買取」
        if ($post->category) {
            $cat = str_replace(['・', ' ', '　'], '', $post->category);
            if ($cat) {
                $tags[] = $cat;
                $tags[] = $cat . '買取';
            }
        }

        // 3. ブランド名 + 「(ブランド)買取」
        if ($post->brand_name) {
            $brand = str_replace([' ', '　'], '', $post->brand_name);
            if ($brand) {
                $tags[] = $brand;
                $tags[] = $brand . '買取';
            }
        }

        // 4. 商品名(型番込み)
        if ($post->product_name) {
            $product = str_replace([' ', '　'], '', $post->product_name);
            if ($product) {
                $tags[] = $product;
            }
        }

        // フォールバック: 何もなければ店舗名
        if (empty($tags) && $post->store) {
            $tags[] = str_replace([' ', '　', '　'], '', $post->store->name);
        }

        return implode(' ', array_map(fn($tag) => '#' . $tag, array_unique($tags)));
    }
}
