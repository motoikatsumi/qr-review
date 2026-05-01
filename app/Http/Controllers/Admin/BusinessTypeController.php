<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use Illuminate\Http\Request;

class BusinessTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $showTrashed = $request->input('show') === 'trashed';
        $query = $showTrashed ? BusinessType::onlyTrashed() : BusinessType::query();
        $businessTypes = $query->orderBy('sort_order')->orderBy('id')->get();
        $trashedCount = BusinessType::onlyTrashed()->count();
        return view('admin.business-types.index', compact('businessTypes', 'showTrashed', 'trashedCount'));
    }

    public function restore($id)
    {
        $bt = BusinessType::onlyTrashed()->findOrFail($id);
        $bt->restore();
        return back()->with('success', "業種「{$bt->name}」を復元しました。");
    }

    public function forceDelete($id)
    {
        $bt = BusinessType::onlyTrashed()->findOrFail($id);
        if ($bt->stores()->withTrashed()->exists()) {
            return back()->with('error', 'この業種を使用している店舗（削除済み含む）があるため完全削除できません。');
        }
        $name = $bt->name;
        $bt->forceDelete();
        return back()->with('success', "業種「{$name}」を完全に削除しました。");
    }

    public function create()
    {
        return view('admin.business-types.create');
    }

    public function store(Request $request)
    {
        // slug が未入力なら name から自動生成
        if (!$request->filled('slug')) {
            $request->merge(['slug' => $this->generateUniqueSlug($request->input('name', ''))]);
        }

        $validated = $request->validate([
            'name'                => 'required|string|max:100',
            'slug'                => 'required|string|max:50|unique:business_types,slug|regex:/^[a-z0-9_]+$/',
            'base_context'        => 'required|string|max:500',
            'focus_presets_raw'   => 'required|string',
            'style_presets_raw'   => 'required|string',
            'ng_words_raw'        => 'nullable|string',
            'review_groups'       => 'nullable|array|max:5',
            'review_groups.*.enabled' => 'nullable|boolean',
            'review_groups.*.label'   => 'nullable|string|max:30',
            'review_groups.*.options_raw' => 'nullable|string',
            'review_groups.*.allow_other_input' => 'nullable|boolean',
            'use_pawn_system'     => 'boolean',
            'use_purchase_posts'  => 'boolean',
            'use_product_rank'    => 'boolean',
            'post_action_word'    => 'nullable|string|max:50',
            'post_title_template' => 'nullable|string|max:500',
            'post_status_options_raw'    => 'nullable|string',
            'post_categories_raw'        => 'nullable|string',
            'reply_category_groups_raw'  => 'nullable|string',
            'post_reason_presets_raw'    => 'nullable|string',
            'post_accessory_presets_raw' => 'nullable|string',
            'post_default_hashtags'      => 'nullable|string',
            'sort_order'          => 'integer|min:0',
            'is_active'           => 'boolean',
        ]);

        $data = $this->parseFormData($validated, $request);
        BusinessType::create($data);

        return redirect('/admin/business-types')->with('success', '業種を追加しました。');
    }

    public function edit(BusinessType $businessType)
    {
        return view('admin.business-types.edit', compact('businessType'));
    }

    public function update(Request $request, BusinessType $businessType)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:100',
            'slug'                => 'required|string|max:50|unique:business_types,slug,' . $businessType->id . '|regex:/^[a-z0-9_]+$/',
            'base_context'        => 'required|string|max:500',
            'focus_presets_raw'   => 'required|string',
            'style_presets_raw'   => 'required|string',
            'ng_words_raw'        => 'nullable|string',
            'review_groups'       => 'nullable|array|max:5',
            'review_groups.*.enabled' => 'nullable|boolean',
            'review_groups.*.label'   => 'nullable|string|max:30',
            'review_groups.*.options_raw' => 'nullable|string',
            'review_groups.*.allow_other_input' => 'nullable|boolean',
            'use_pawn_system'     => 'boolean',
            'use_purchase_posts'  => 'boolean',
            'use_product_rank'    => 'boolean',
            'post_action_word'    => 'nullable|string|max:50',
            'post_title_template' => 'nullable|string|max:500',
            'post_status_options_raw'    => 'nullable|string',
            'post_categories_raw'        => 'nullable|string',
            'reply_category_groups_raw'  => 'nullable|string',
            'post_reason_presets_raw'    => 'nullable|string',
            'post_accessory_presets_raw' => 'nullable|string',
            'post_default_hashtags'      => 'nullable|string',
            'sort_order'          => 'integer|min:0',
            'is_active'           => 'boolean',
        ]);

        $data = $this->parseFormData($validated, $request);
        $businessType->update($data);

        return redirect('/admin/business-types')->with('success', '業種を更新しました。');
    }

    public function destroy(BusinessType $businessType)
    {
        if ($businessType->stores()->exists()) {
            return back()->with('error', 'この業種を使用している店舗があるため削除できません。');
        }
        $businessType->delete();
        return redirect('/admin/business-types')->with('success', '業種を削除しました。');
    }

    /**
     * AI 入力サポート：業種名から各設定項目の候補を生成
     * フロント (業種編集画面) から XHR で呼ばれる
     */
    public function aiSuggest(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:100',
            'target' => 'required|string|in:base_context,focus_presets,style_presets,ng_words,review_options,post_settings,all',
        ]);

        $industry = $validated['name'];
        $gemini = new \App\Services\GeminiService();

        try {
            if ($validated['target'] === 'base_context' || $validated['target'] === 'all') {
                $result['base_context'] = $this->askGeminiForText($gemini,
                    "業種『{$industry}』のお客様が店舗で得る体験を、AI 口コミ生成用のコンテキスト文として一文で表現してください。例：『質屋・買取店でのサービス体験（査定・買取・質入れなど）』『焼肉店での食事・接客・雰囲気に関する体験』。50 文字以内。出力は説明文のみ、本文に 1 行で記述してください。"
                );
            }
            if ($validated['target'] === 'focus_presets' || $validated['target'] === 'all') {
                $result['focus_presets'] = $this->askGeminiForLines($gemini,
                    "業種『{$industry}』の Google 口コミで AI が文章を書く際の『切り口』のバリエーションを 15 個生成してください。各項目は『〇〇を中心に』『△△を交えて』の形式で 20 文字前後。例：『お店の雰囲気や居心地も交えて』『スタッフの丁寧さを中心に』。出力は 1 行 1 項目、15 項目だけを列挙してください（番号や箇条書き記号は付けない）。"
                );
            }
            if ($validated['target'] === 'style_presets' || $validated['target'] === 'all') {
                $result['style_presets'] = $this->askGeminiForLines($gemini,
                    "業種『{$industry}』の口コミを書く『書き手のスタイル（ペルソナ）』を 10 個生成してください。例：『初めてこの店を利用した新規のお客様』『何度も利用しているリピーターのお客様』『友人に紹介されて来店したお客様』。各項目 20 文字前後、出力は 1 行 1 項目、10 項目だけ列挙（番号付けしない）。"
                );
            }
            if ($validated['target'] === 'ng_words' || $validated['target'] === 'all') {
                $result['ng_words'] = $this->askGeminiForLines($gemini,
                    "業種『{$industry}』の口コミで使うべきでないワード（業界的にタブー・誤解を招く・差別的・過剰な表現など）を 10 個以内で列挙してください。1 行 1 ワード、単語のみ（説明文なし）。ありきたりで頻出するものを優先してください。"
                );
            }
            if ($validated['target'] === 'post_settings' || $validated['target'] === 'all') {
                // 投稿機能設定（WordPress / SNS 投稿の各種項目）を一括 JSON 生成
                $isPawnLike = (stripos($industry, '質屋') !== false || stripos($industry, '買取') !== false);
                $categoryFormatHint = $isPawnLike
                    ? '質屋・買取店の場合は「表示名|WPスラッグ|WPパス」の形式（例: 「時計|時計|/items/clock」）。'
                    : 'カテゴリは「名前のみ」で OK（WPスラッグ・パス不要）。例: 「軽自動車」「SUV」など。';

                $postSettings = $this->askGeminiForObject($gemini,
                    "業種『{$industry}』の WordPress / SNS 投稿機能の設定を JSON で生成してください。\n\n"
                    . "★必ず以下 7 つのキーをすべて含む完全な JSON オブジェクトで返してください。各キーは省略不可、\n"
                    . "  空文字や空配列は post_accessory_presets を除き使用しないでください。\n\n"
                    . '{' . "\n"
                    . '  "post_action_word": "（必須）業種に合う動詞 1 つ",' . "\n"
                    . '  "post_title_template": "（必須）投稿タイトルの 1 文。プレースホルダ {brand} {product} {status} {category} を 1 つ以上使うこと",' . "\n"
                    . '  "post_status_options": ["（必須・3〜5 個）", "...", "..."],' . "\n"
                    . '  "post_categories": ["（必須・5〜8 個）", "...", "..."],' . "\n"
                    . '  "post_reason_presets": ["（必須・5〜8 個）", "...", "..."],' . "\n"
                    . '  "post_accessory_presets": ["（5〜10 個。業種に該当する概念がなければ [] でも可）"],' . "\n"
                    . '  "post_default_hashtags": ["（必須・5〜8 個・# は付けない）", "...", "..."]' . "\n"
                    . '}' . "\n\n"
                    . "【業種別ガイドライン】\n"
                    . "- post_action_word: 例 — 質屋『お買取り』 / 焼肉店『ご紹介』 / 中古車『お取り扱い』 / 美容室『施術』 / 不動産『ご紹介』\n"
                    . "- post_title_template: 業種に自然な定型文。例 — 質屋『{brand} {product} {status}をお買取りいたしました。』 / 焼肉店『{category}メニューをご紹介します。』 / 美容室『{category}メニューのご案内です。』\n"
                    . "- post_status_options: 業種の文脈で使える 3〜5 個。例 — 物販系『中古品/新品/未使用品』 / 飲食店『通常/期間限定/新メニュー』 / 美容室『定番/期間限定/新メニュー』\n"
                    . "- post_categories: 業種に合うサブカテゴリ。{$categoryFormatHint}\n"
                    . "- post_reason_presets: 顧客の来店/購入理由 5〜8 個。例 — 質屋『使わなくなった/引っ越し/資金が必要』 / 美容室『イメチェン/白髪が気になる/ダメージケア』\n"
                    . "- post_accessory_presets: 物販系のみ。飲食・美容・サービス業は空配列 []\n"
                    . "- post_default_hashtags: 業種で頻出する SNS タグ 5〜8 個。# は付けない。\n\n"
                    . "★出力ルール: JSON のみ、前置き・説明・コードブロック禁止。"
                );
                $result['post_settings'] = [
                    'post_action_word'       => $postSettings['post_action_word'] ?? '',
                    'post_title_template'    => $postSettings['post_title_template'] ?? '',
                    'post_status_options'    => $postSettings['post_status_options'] ?? [],
                    'post_categories'        => $postSettings['post_categories'] ?? [],
                    'post_reason_presets'    => $postSettings['post_reason_presets'] ?? [],
                    'post_accessory_presets' => $postSettings['post_accessory_presets'] ?? [],
                    'post_default_hashtags'  => $postSettings['post_default_hashtags'] ?? [],
                ];
            }
            if ($validated['target'] === 'review_options' || $validated['target'] === 'all') {
                // 口コミフォームの質問項目候補（visit_type と item を 1 回の JSON 呼び出しで取得）
                $reviewOpts = $this->askGeminiForObject($gemini,
                    "業種『{$industry}』の口コミフォームで、回答者に訊く選択肢を JSON で生成してください。\n"
                    . "以下の 2 項目を含む JSON オブジェクトで回答:\n"
                    . '{"visit_type_label": "来店", "visit_type": ["初めて", "リピーター", ...],' . "\n"
                    . ' "item_label": "品目", "item": ["選択肢1", "選択肢2", ...]}' . "\n\n"
                    . "ルール:\n"
                    . "- visit_type: 来店の種類を 4〜5 個（例『初めて』『リピーター』『家族と』『友人の紹介』）\n"
                    . "- item: 利用したメニュー/品目/サービスを 5〜6 個。業種『{$industry}』に合う具体例\n"
                    . "- 例（焼肉店）: コース, 単品, ホルモン, 飲み物, ランチ\n"
                    . "- 例（美容室）: カット, カラー, パーマ, トリートメント, ヘッドスパ\n"
                    . "- 日本語のみ、JSON のみ出力（前置き・説明・思考は禁止）"
                );
                $result['review_options'] = [
                    'visit_type_label' => $reviewOpts['visit_type_label'] ?? '来店',
                    'visit_type'       => $reviewOpts['visit_type'] ?? [],
                    'item_label'       => $reviewOpts['item_label'] ?? '品目',
                    'item'              => $reviewOpts['item'] ?? [],
                ];
            }

            return response()->json(['success' => true, 'data' => $result ?? []]);
        } catch (\Exception $e) {
            \Log::error('BusinessType aiSuggest failed', ['error' => $e->getMessage(), 'industry' => $industry]);
            return response()->json(['success' => false, 'error' => 'AI 生成に失敗しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Gemini に 1 テキストを生成させる
     */
    private function askGeminiForText(\App\Services\GeminiService $gemini, string $prompt): string
    {
        $text = $this->callGemini($gemini, $prompt);
        // 先頭・末尾の改行や記号を除去
        $text = trim($text);
        $text = preg_replace('/^[\-\*\d+\.\s]+/', '', $text);
        return mb_substr($text, 0, 100);
    }

    /**
     * Gemini に JSON 配列形式でリストを生成させる
     */
    private function askGeminiForLines(\App\Services\GeminiService $gemini, string $prompt): array
    {
        // JSON 配列で出力させる（思考プロセス・前置き・英語を回避）
        $prompt .= "\n\n"
            . "【出力形式】必ず次の JSON 配列形式だけを出力してください。説明・前置き・コードブロック記号 (```) は一切不要。\n"
            . '例: ["選択肢1", "選択肢2", "選択肢3"]';

        $text = $this->callGemini($gemini, $prompt, true);
        $text = trim($text);
        // コードブロック記号を除去
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
        $text = trim($text);

        // JSON パース
        $data = json_decode($text, true);
        if (!is_array($data)) {
            // JSON が返らなかった場合の保険：行単位でもパース
            $lines = preg_split('/\r?\n/', $text);
            $data = [];
            foreach ($lines as $line) {
                $line = trim($line);
                $line = preg_replace('/^["\-\*・●\d+\.\s)\(]+|["\s,]+$/u', '', $line ?? '');
                if (!$line) continue;
                if (!preg_match('/\p{Han}|\p{Hiragana}|\p{Katakana}/u', $line)) continue;
                $data[] = $line;
            }
        }

        $result = [];
        foreach ($data as $item) {
            if (!is_string($item)) continue;
            $item = trim($item);
            if ($item === '') continue;
            // 日本語文字を含まない行は除外
            if (!preg_match('/\p{Han}|\p{Hiragana}|\p{Katakana}/u', $item)) continue;
            // 末尾の句読点を除去（マルチバイト安全）
            $item = preg_replace('/[。．\.、,]+$/u', '', $item);
            $result[] = trim($item);
        }
        return array_values(array_unique(array_filter($result)));
    }

    /**
     * Gemini に JSON オブジェクトを生成させる
     */
    private function askGeminiForObject(\App\Services\GeminiService $gemini, string $prompt): array
    {
        $text = $this->callGemini($gemini, $prompt, true);
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
        $data = json_decode(trim($text), true);
        return is_array($data) ? $data : [];
    }

    /**
     * GeminiService 内の汎用呼び出し
     */
    private function callGemini(\App\Services\GeminiService $gemini, string $prompt, bool $jsonMode = false): string
    {
        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        if (!$apiKey) {
            throw new \RuntimeException('GEMINI_API_KEY が未設定です');
        }
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;
        $config = [
            'temperature' => 0.7,
            'maxOutputTokens' => 3000,
            // Gemini 2.5 の思考トレースが本文に混入するのを防ぐ
            'thinkingConfig' => ['thinkingBudget' => 0],
        ];
        if ($jsonMode) {
            // JSON モード（Gemini は response_mime_type で JSON 固定可能）
            $config['response_mime_type'] = 'application/json';
        }
        $resp = \Illuminate\Support\Facades\Http::timeout(45)->post($url, [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => $config,
        ]);
        if (!$resp->successful()) {
            throw new \RuntimeException('Gemini API error: ' . $resp->status() . ' - ' . substr($resp->body(), 0, 200));
        }
        $text = data_get($resp->json(), 'candidates.0.content.parts.0.text', '');
        return trim((string) $text);
    }

    /**
     * フォームの改行区切りテキストをJSON配列用のarrayに変換
     */
    private function parseFormData(array $validated, Request $request): array
    {
        // スロット位置 → 内部キー（UI からは隠蔽）
        $slotKeys = ['gender', 'visit_type', 'age', 'item', 'custom1'];

        // review_groups の 5 スロットを JSON 配列に整形
        $rawGroups = $request->input('review_groups', []);
        $reviewGroups = [];
        $visitTypeOpts = [];
        $reviewItemOpts = [];
        for ($idx = 0; $idx < 5; $idx++) {
            $slot = $rawGroups[$idx] ?? [];
            $enabled = !empty($slot['enabled']);
            $allowOther = !empty($slot['allow_other_input']);
            $key     = $slotKeys[$idx];
            $label   = trim($slot['label'] ?? '');
            $opts    = $this->splitLines($slot['options_raw'] ?? '');
            $reviewGroups[] = [
                'key'               => $key,
                'label'             => $label ?: $key,
                'options'           => $opts,
                'enabled'           => $enabled,
                'allow_other_input' => $allowOther,
            ];
            // 後方互換: visit_type / item は既存カラムにも反映
            if ($enabled && $key === 'visit_type') {
                $visitTypeOpts = $opts;
            } elseif ($enabled && $key === 'item') {
                $reviewItemOpts = $opts;
            }
        }

        return [
            'name'               => $validated['name'],
            'slug'               => $validated['slug'],
            'base_context'       => $validated['base_context'],
            'focus_presets'      => $this->splitLines($validated['focus_presets_raw']),
            'style_presets'      => $this->splitLines($validated['style_presets_raw']),
            'ng_words'           => $this->splitLines($validated['ng_words_raw'] ?? ''),
            'visit_type_options' => $visitTypeOpts,
            'review_item_options' => $reviewItemOpts,
            'review_option_groups' => $reviewGroups,
            'use_pawn_system'    => $request->boolean('use_pawn_system'),
            'use_purchase_posts' => $request->boolean('use_purchase_posts'),
            'use_product_rank'   => $request->boolean('use_product_rank'),
            'post_action_word'   => $validated['post_action_word'] ?? null,
            'post_title_template' => $validated['post_title_template'] ?? null,
            'post_status_options'    => $this->splitLines($validated['post_status_options_raw'] ?? ''),
            'post_categories'        => $this->parseCategoryLines($validated['post_categories_raw'] ?? ''),
            'reply_category_groups'  => $this->parseGroupedLines($validated['reply_category_groups_raw'] ?? ''),
            'post_reason_presets'    => $this->splitLines($validated['post_reason_presets_raw'] ?? ''),
            'post_accessory_presets' => $this->splitLines($validated['post_accessory_presets_raw'] ?? ''),
            'post_default_hashtags' => $validated['post_default_hashtags'] ?? null,
            'sort_order'         => (int) ($validated['sort_order'] ?? 0),
            'is_active'          => $request->boolean('is_active'),
        ];
    }

    /**
     * 業種名から自動的に英数字スラッグを生成（日本語→ローマ字は難しいので適当な英数字）
     */
    private function generateUniqueSlug(string $name): string
    {
        // 日本語が混じる前提でシンプルに：英字/数字/アンダースコアを残し、それ以外は削る
        $base = preg_replace('/[^a-zA-Z0-9_]+/', '', strtolower($name));
        if (!$base) {
            // 日本語のみの場合はランダム英数字
            $base = 'type_' . strtolower(substr(md5($name . microtime()), 0, 8));
        }
        $slug = substr($base, 0, 40);
        // 重複回避
        $candidate = $slug;
        $n = 1;
        while (\App\Models\BusinessType::where('slug', $candidate)->exists()) {
            $candidate = $slug . '_' . $n++;
            if ($n > 999) break;
        }
        return $candidate;
    }

    private function splitLines(string $text): array
    {
        return array_values(array_filter(
            array_map('trim', explode("\n", str_replace("\r\n", "\n", $text)))
        ));
    }

    /**
     * 空行で区切られた複数のテキストブロックを「配列の配列」にパース。
     * Google返信のテーマ別商品グループ用(reply_category_groups)。
     * 例:
     *   ブランド品
     *   時計
     *
     *   ゲーム
     *   楽器
     * → [['ブランド品','時計'], ['ゲーム','楽器']]
     */
    private function parseGroupedLines(string $text): array
    {
        $blocks = preg_split('/\n\s*\n+/', trim(str_replace("\r\n", "\n", $text)));
        $groups = [];
        foreach ($blocks ?: [] as $block) {
            $items = $this->splitLines($block);
            if (!empty($items)) $groups[] = $items;
        }
        return $groups;
    }

    /**
     * カテゴリ行（name|wp_slug|wp_path）をパースしてJSON配列化
     */
    private function parseCategoryLines(string $text): array
    {
        $lines = $this->splitLines($text);
        $categories = [];
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (!empty($parts[0])) {
                $categories[] = [
                    'name'    => $parts[0],
                    'wp_slug' => $parts[1] ?? $parts[0],
                    'wp_path' => $parts[2] ?? '',
                ];
            }
        }
        return $categories;
    }
}
