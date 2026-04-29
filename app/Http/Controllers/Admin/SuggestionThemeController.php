<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use App\Models\SiteSetting;
use App\Models\SuggestionCategory;
use App\Models\SuggestionTheme;
use Illuminate\Http\Request;

class SuggestionThemeController extends Controller
{
    /**
     * カテゴリ・テーマ一覧
     */
    public function index(Request $request)
    {
        $showTrashed = $request->input('show') === 'trashed';

        if ($showTrashed) {
            // 削除済みカテゴリ + 削除済みテーマ（個別に表示するため）
            $trashedCategories = SuggestionCategory::onlyTrashed()->with('businessType')->orderBy('deleted_at', 'desc')->get();
            $trashedThemes = SuggestionTheme::onlyTrashed()->with('category')->orderBy('deleted_at', 'desc')->get();
            $categories = collect();
        } else {
            $categories = SuggestionCategory::with(['businessType', 'themes' => function ($q) {
                $q->orderBy('sort_order');
            }])->orderBy('sort_order')->get();
            $trashedCategories = collect();
            $trashedThemes = collect();
        }

        $displayCount = SiteSetting::get('suggestion_display_count', '6');
        $businessTypes = BusinessType::where('is_active', true)->orderBy('sort_order')->get();
        $trashedCount = SuggestionCategory::onlyTrashed()->count() + SuggestionTheme::onlyTrashed()->count();

        return view('admin.suggestion-themes.index', compact(
            'categories', 'displayCount', 'businessTypes',
            'showTrashed', 'trashedCategories', 'trashedThemes', 'trashedCount'
        ));
    }

    public function restoreCategory($id)
    {
        $cat = SuggestionCategory::onlyTrashed()->findOrFail($id);
        $cat->restore();
        return back()->with('success', "カテゴリ「{$cat->name}」を復元しました。");
    }

    public function forceDeleteCategory($id)
    {
        $cat = SuggestionCategory::onlyTrashed()->findOrFail($id);
        $name = $cat->name;
        $cat->themes()->forceDelete();
        $cat->forceDelete();
        return back()->with('success', "カテゴリ「{$name}」を完全に削除しました。");
    }

    public function restoreTheme($id)
    {
        $theme = SuggestionTheme::onlyTrashed()->findOrFail($id);
        $theme->restore();
        return back()->with('success', "テーマ「{$theme->label}」を復元しました。");
    }

    public function forceDeleteTheme($id)
    {
        $theme = SuggestionTheme::onlyTrashed()->findOrFail($id);
        $label = $theme->label;
        $theme->forceDelete();
        return back()->with('success', "テーマ「{$label}」を完全に削除しました。");
    }

    /**
     * 表示テーマ数を更新
     */
    public function updateDisplayCount(Request $request)
    {
        $validated = $request->validate([
            'display_count' => 'required|integer|min:1|max:50',
        ]);

        SiteSetting::set('suggestion_display_count', $validated['display_count']);

        return redirect('/admin/suggestion-themes')->with('success', '表示テーマ数を更新しました。');
    }

    /**
     * カテゴリ作成
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'business_type_id' => 'nullable|exists:business_types,id',
        ]);

        $maxOrder = SuggestionCategory::max('sort_order') ?? 0;
        SuggestionCategory::create([
            'name' => $validated['name'],
            'business_type_id' => $validated['business_type_id'] ?? null,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect('/admin/suggestion-themes')->with('success', 'カテゴリを追加しました。');
    }

    /**
     * カテゴリ更新
     */
    public function updateCategory(Request $request, SuggestionCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'business_type_id' => 'nullable|exists:business_types,id',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update([
            'name' => $validated['name'],
            'business_type_id' => $validated['business_type_id'] ?? null,
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->has('is_active'),
        ]);

        return redirect('/admin/suggestion-themes')->with('success', 'カテゴリを更新しました。');
    }

    /**
     * カテゴリ削除
     */
    public function destroyCategory(SuggestionCategory $category)
    {
        $category->delete();
        return redirect('/admin/suggestion-themes')->with('success', 'カテゴリを削除しました。');
    }

    /**
     * テーマ追加
     */
    public function storeTheme(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:suggestion_categories,id',
            'icon' => 'required|string|max:10',
            'label' => 'required|string|max:50',
            'keyword' => 'required|string|max:200',
        ]);

        $maxOrder = SuggestionTheme::where('category_id', $validated['category_id'])->max('sort_order') ?? 0;
        SuggestionTheme::create([
            'category_id' => $validated['category_id'],
            'icon' => $validated['icon'],
            'label' => $validated['label'],
            'keyword' => $validated['keyword'],
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect('/admin/suggestion-themes')->with('success', 'テーマを追加しました。');
    }

    /**
     * テーマ更新
     */
    public function updateTheme(Request $request, SuggestionTheme $theme)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:suggestion_categories,id',
            'icon' => 'required|string|max:10',
            'label' => 'required|string|max:50',
            'keyword' => 'required|string|max:200',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $theme->update([
            'category_id' => $validated['category_id'],
            'icon' => $validated['icon'],
            'label' => $validated['label'],
            'keyword' => $validated['keyword'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->has('is_active'),
        ]);

        return redirect('/admin/suggestion-themes')->with('success', 'テーマを更新しました。');
    }

    /**
     * テーマ削除
     */
    public function destroyTheme(SuggestionTheme $theme)
    {
        $theme->delete();
        return redirect('/admin/suggestion-themes')->with('success', 'テーマを削除しました。');
    }

    /**
     * AI で口コミテーマ（カテゴリ＋テーマ）を生成
     * 業種を指定すると、その業種に適したカテゴリ 4〜6 個と各カテゴリにテーマ 4〜6 個を生成して返す
     */
    public function aiSuggest(Request $request)
    {
        $validated = $request->validate([
            'business_type_id' => 'nullable|exists:business_types,id',
        ]);

        $businessType = !empty($validated['business_type_id'])
            ? BusinessType::find($validated['business_type_id'])
            : null;
        $industry = $businessType?->name ?? '一般的なお店';

        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['success' => false, 'error' => 'GEMINI_API_KEY が未設定です'], 500);
        }

        $prompt = "業種『{$industry}』のお客様が口コミを書く時に使う『口コミテーマ』を生成してください。\n\n"
            . "■カテゴリ：4〜6 個（例：『価格・金額』『接客』『商品・サービス』『店内の雰囲気』）\n"
            . "■各カテゴリのテーマ：4〜6 個。それぞれに以下を含める：\n"
            . "  - icon: 1文字の絵文字（💰😊⭐🍖など）\n"
            . "  - label: 顧客がボタンとしてタップする短い表示名（最大 12 文字）\n"
            . "  - keyword: AI 生成プロンプトに渡すキーワード（最大 50 文字、口コミ本文に含めたい言葉）\n\n"
            . "【出力】JSON のみ。例：\n"
            . '{"categories":[{"name":"価格・金額","themes":[{"icon":"💰","label":"高価買取","keyword":"査定価格が高くて満足、高価買取"}]}]}';

        try {
            $text = $this->callGemini($prompt, true);
            $text = trim($text);
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
            $data = json_decode(trim($text), true);

            if (!is_array($data) || !isset($data['categories']) || !is_array($data['categories'])) {
                return response()->json(['success' => false, 'error' => 'AI 出力の解析に失敗しました'], 500);
            }

            $clean = [];
            foreach ($data['categories'] as $cat) {
                if (empty($cat['name'])) continue;
                $themes = [];
                foreach (($cat['themes'] ?? []) as $t) {
                    if (empty($t['label']) || empty($t['keyword'])) continue;
                    $themes[] = [
                        'icon' => mb_substr(trim($t['icon'] ?? '⭐'), 0, 4),
                        'label' => mb_substr(trim($t['label']), 0, 50),
                        'keyword' => mb_substr(trim($t['keyword']), 0, 200),
                    ];
                }
                if (empty($themes)) continue;
                $clean[] = [
                    'name' => mb_substr(trim($cat['name']), 0, 50),
                    'themes' => $themes,
                ];
            }

            return response()->json(['success' => true, 'data' => ['categories' => $clean]]);
        } catch (\Exception $e) {
            \Log::error('SuggestionTheme aiSuggest failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'AI 生成に失敗しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * AI で生成したカテゴリ＋テーマを一括保存
     */
    public function aiApply(Request $request)
    {
        $validated = $request->validate([
            'business_type_id' => 'nullable|exists:business_types,id',
            'categories' => 'required|array|min:1',
            'categories.*.name' => 'required|string|max:50',
            'categories.*.themes' => 'required|array|min:1',
            'categories.*.themes.*.icon' => 'required|string|max:10',
            'categories.*.themes.*.label' => 'required|string|max:50',
            'categories.*.themes.*.keyword' => 'required|string|max:200',
        ]);

        $businessTypeId = $validated['business_type_id'] ?? null;
        $maxCatOrder = SuggestionCategory::max('sort_order') ?? 0;
        $createdCategories = 0;
        $createdThemes = 0;

        foreach ($validated['categories'] as $cat) {
            $maxCatOrder++;
            $category = SuggestionCategory::create([
                'name' => $cat['name'],
                'business_type_id' => $businessTypeId,
                'sort_order' => $maxCatOrder,
                'is_active' => true,
            ]);
            $createdCategories++;

            $themeOrder = 0;
            foreach ($cat['themes'] as $t) {
                $themeOrder++;
                SuggestionTheme::create([
                    'category_id' => $category->id,
                    'icon' => $t['icon'],
                    'label' => $t['label'],
                    'keyword' => $t['keyword'],
                    'sort_order' => $themeOrder,
                    'is_active' => true,
                ]);
                $createdThemes++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "カテゴリ {$createdCategories} 件、テーマ {$createdThemes} 件を追加しました。",
        ]);
    }

    /**
     * Gemini API 呼び出し
     */
    private function callGemini(string $prompt, bool $jsonMode = false): string
    {
        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;
        $config = [
            'temperature' => 0.7,
            'maxOutputTokens' => 4000,
            'thinkingConfig' => ['thinkingBudget' => 0],
        ];
        if ($jsonMode) {
            $config['response_mime_type'] = 'application/json';
        }
        $resp = \Illuminate\Support\Facades\Http::timeout(60)->post($url, [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => $config,
        ]);
        if (!$resp->successful()) {
            throw new \RuntimeException('Gemini API error: ' . $resp->status());
        }
        return trim((string) data_get($resp->json(), 'candidates.0.content.parts.0.text', ''));
    }
}
