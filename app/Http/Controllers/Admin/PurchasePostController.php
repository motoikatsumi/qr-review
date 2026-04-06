<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchasePost;
use App\Models\Store;
use App\Services\GeminiService;
use App\Services\GoogleBusinessService;
use App\Services\PawnSystemService;
use App\Services\WordPressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurchasePostController extends Controller
{
    /**
     * カテゴリとGBPカテゴリ・LP URLのマッピング
     */
    protected array $categoryMap = [
        'ゲーム・ソフト' => ['slug' => 'ゲーム', 'url' => 'https://78assist.com/items/other'],
        '時計' => ['slug' => '時計', 'url' => 'https://78assist.com/items/clock'],
        'ブランド品' => ['slug' => 'ブランド品', 'url' => 'https://78assist.com/items/brand'],
        '貴金属' => ['slug' => 'ジュエリー', 'url' => 'https://78assist.com/items/gold'],
        '金券' => ['slug' => '硬貨', 'url' => 'https://78assist.com/items/other'],
        'カメラ・レンズ' => ['slug' => 'カメラ', 'url' => 'https://78assist.com/items/camera'],
        '電化製品' => ['slug' => 'パソコン', 'url' => 'https://78assist.com/items/electricity'],
        '電動工具' => ['slug' => 'スポーツ用品', 'url' => 'https://78assist.com/items/electric-tool'],
        'お酒' => ['slug' => 'アルコール', 'url' => 'https://78assist.com/items/other'],
        'スマホ・タブレット' => ['slug' => 'スマホ・タブレット', 'url' => 'https://78assist.com/items/smartphone'],
        '楽器' => ['slug' => '楽器', 'url' => 'https://78assist.com/items/instrument'],
        'ダイヤモンド' => ['slug' => 'ダイヤモンド', 'url' => 'https://78assist.com/items/gold'],
        'プラチナ' => ['slug' => 'プラチナ', 'url' => 'https://78assist.com/items/gold'],
        '宝石' => ['slug' => '宝石', 'url' => 'https://78assist.com/items/gold'],
        'DVDブルーレイ' => ['slug' => 'DVDブルーレイ', 'url' => 'https://78assist.com/items/other'],
        'おもちゃ' => ['slug' => 'おもちゃ', 'url' => 'https://78assist.com/items/other'],
        'フィギュア' => ['slug' => 'フィギュア', 'url' => 'https://78assist.com/items/other'],
        '健康器具' => ['slug' => '健康器具', 'url' => 'https://78assist.com/items/other'],
        'カー用品' => ['slug' => 'カー用品', 'url' => 'https://78assist.com/items/other'],
    ];

    /**
     * 投稿一覧
     */
    public function index(Request $request)
    {
        $query = PurchasePost::with('store')->orderBy('created_at', 'desc');

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

        return view('admin.purchase-posts.index', compact('posts', 'stores'));
    }

    /**
     * 新規作成フォーム
     */
    public function create()
    {
        $stores = Store::where('is_active', true)->orderBy('name')->get();
        $categories = array_keys($this->categoryMap);

        return view('admin.purchase-posts.create', compact('stores', 'categories'));
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
        $request->validate([
            'brand_name' => 'required|string|max:200',
            'product_name' => 'required|string|max:200',
        ]);

        $params = $request->only([
            'brand_name', 'product_name', 'customer_gender', 'customer_age',
            'customer_reason', 'product_condition', 'accessories',
        ]);

        // フォームデータのエンコーディングを修正
        foreach ($params as $key => $value) {
            if (is_string($value) && $value !== '') {
                // 不正なUTF-8バイトを除去
                $params[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
        }

        $gemini = new GeminiService();
        $text = $gemini->generatePurchaseEpisode($params);

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
        $text = $gemini->generateStoreFooterTemplate($store->name, $request->area);

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
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'brand_name' => 'required|string|max:200',
            'product_name' => 'required|string|max:200',
            'product_status' => 'required|string|max:50',
            'category' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::in(array_keys($this->categoryMap))],
            'block1_text' => 'required|string',
            'block2_text' => 'required|string',
            'block3_text' => 'required|string',
            'image' => 'required|image|min:11|max:10240|dimensions:min_width=250,min_height=250',
            'wp_tag_name' => 'nullable|string|max:100',
            'rank' => 'nullable|string|in:S,A,B,C,D',
        ], [
            'image.min' => '画像ファイルサイズが小さすぎます（最低11KB必要です。Google APIの要件: 10KB以上）。',
        ]);

        $store = Store::findOrFail($request->store_id);

        // 重複チェック（同じ店舗・ブランド・商品名の投稿が24時間以内にあるか）
        $duplicate = PurchasePost::where('store_id', $request->store_id)
            ->where('brand_name', $request->brand_name)
            ->where('product_name', $request->product_name)
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', '同じ店舗・ブランド名・商品名の投稿が24時間以内に存在します。重複投稿でないか確認してください。');
        }

        // 画像をローカルに一時保存
        $imagePath = $request->file('image')->store('purchase-posts', 'public');
        $fullImagePath = storage_path('app/public/' . $imagePath);

        // 全文組み立て（○○をカテゴリ名に置換）
        $block3Replaced = str_replace('○○', $request->category, $request->block3_text);
        $fullText = $request->block1_text . "\n\n" . $request->block2_text . "\n\n" . $block3Replaced;

        // カテゴリマッピング
        $catInfo = $this->categoryMap[$request->category] ?? ['slug' => 'blog', 'url' => ''];

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
        ]);

        // --- WordPress 投稿 ---
        $this->publishToWordPress($post, $fullImagePath);

        // --- Google ビジネス投稿（最新情報） ---
        $this->publishToGooglePost($post, $catInfo);

        // --- Google ビジネス写真ギャラリー ---
        $this->publishToGooglePhoto($post);

        $post->published_at = now();
        $post->save();

        $messages = [];
        if ($post->wp_status === 'published') $messages[] = 'WordPress投稿完了';
        if ($post->wp_status === 'failed') $messages[] = 'WordPress投稿失敗: ' . $post->wp_error;
        if ($post->google_post_status === 'published') $messages[] = 'Google投稿完了';
        if ($post->google_post_status === 'failed') $messages[] = 'Google投稿失敗: ' . $post->google_post_error;
        if ($post->google_photo_status === 'published') $messages[] = 'Google写真追加完了';
        if ($post->google_photo_status === 'failed') $messages[] = 'Google写真追加失敗: ' . $post->google_photo_error;

        $hasError = $post->wp_status === 'failed' || $post->google_post_status === 'failed' || $post->google_photo_status === 'failed';

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
        $stores = Store::where('is_active', true)->orderBy('name')->get();
        $categories = array_keys($this->categoryMap);

        return view('admin.purchase-posts.edit', compact('purchasePost', 'stores', 'categories'));
    }

    /**
     * 投稿を更新（ローカルデータのみ）
     */
    public function update(Request $request, PurchasePost $purchasePost)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'brand_name' => 'required|string|max:200',
            'product_name' => 'required|string|max:200',
            'product_status' => 'required|string|max:50',
            'category' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::in(array_keys($this->categoryMap))],
            'block1_text' => 'required|string',
            'block2_text' => 'required|string',
            'block3_text' => 'required|string',
            'image' => 'nullable|image|min:11|max:10240|dimensions:min_width=250,min_height=250',
            'wp_tag_name' => 'nullable|string|max:100',
            'rank' => 'nullable|string|in:S,A,B,C,D',
        ]);

        $catInfo = $this->categoryMap[$request->category] ?? ['slug' => 'blog', 'url' => ''];
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
        $fullImagePath = storage_path('app/public/' . $purchasePost->image_path);
        $catInfo = $this->categoryMap[$purchasePost->category] ?? ['slug' => 'blog', 'url' => ''];
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

        $purchasePost->save();

        $hasError = $purchasePost->wp_status === 'failed' || $purchasePost->google_post_status === 'failed' || $purchasePost->google_photo_status === 'failed';

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
                $wp = new WordPressService();
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

        // ローカル画像を削除
        if ($purchasePost->image_path) {
            Storage::disk('public')->delete($purchasePost->image_path);
        }

        $purchasePost->delete();

        return redirect()->route('admin.purchase-posts.index')
            ->with('success', '投稿を削除しました');
    }

    // ==============================
    // Private Publishing Methods
    // ==============================

    protected function publishToWordPress(PurchasePost $post, string $imagePath): void
    {
        try {
            $wp = new WordPressService();

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

            $title = $post->brand_name . ' ' . $post->product_name . ' ' . $post->product_status . 'をお買取りいたしました';

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
            $actionUrl = $catInfo['url'] ?? null;

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
}
