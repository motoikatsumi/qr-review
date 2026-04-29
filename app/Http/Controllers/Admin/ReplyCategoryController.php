<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use App\Models\ReplyCategory;
use App\Models\ReplyKeyword;
use Illuminate\Http\Request;

class ReplyCategoryController extends Controller
{
    /**
     * 返信カテゴリ・キーワード管理画面
     */
    public function index(Request $request)
    {
        $showTrashed = $request->input('show') === 'trashed';
        // 業種フィルタ: '' = すべて, 'common' = 業種共通(null), 'N' = 業種 ID
        $filterBt = $request->input('business_type', '');

        if ($showTrashed) {
            $trashedCategories = ReplyCategory::onlyTrashed()->with('businessType')->orderBy('deleted_at', 'desc')->get();
            $trashedKeywords = ReplyKeyword::onlyTrashed()->with('category')->orderBy('deleted_at', 'desc')->get();
            $categories = collect();
        } else {
            $query = ReplyCategory::with(['businessType', 'keywords' => function ($q) {
                $q->orderBy('sort_order');
            }])->orderBy('sort_order');

            if ($filterBt === 'common') {
                $query->whereNull('business_type_id');
            } elseif ($filterBt !== '' && is_numeric($filterBt)) {
                $query->where('business_type_id', (int) $filterBt);
            }

            $categories = $query->get();
            $trashedCategories = collect();
            $trashedKeywords = collect();
        }

        $businessTypes = BusinessType::where('is_active', true)->orderBy('sort_order')->get();
        $trashedCount = ReplyCategory::onlyTrashed()->count() + ReplyKeyword::onlyTrashed()->count();

        return view('admin.reply-categories.index', compact(
            'categories', 'businessTypes', 'filterBt',
            'showTrashed', 'trashedCategories', 'trashedKeywords', 'trashedCount'
        ));
    }

    public function restoreCategory($id)
    {
        $cat = ReplyCategory::onlyTrashed()->findOrFail($id);
        $cat->restore();
        return back()->with('success', "カテゴリ「{$cat->name}」を復元しました。");
    }

    public function forceDeleteCategory($id)
    {
        $cat = ReplyCategory::onlyTrashed()->findOrFail($id);
        $name = $cat->name;
        $cat->keywords()->forceDelete();
        $cat->forceDelete();
        return back()->with('success', "カテゴリ「{$name}」を完全に削除しました。");
    }

    public function restoreKeyword($id)
    {
        $kw = ReplyKeyword::onlyTrashed()->findOrFail($id);
        $kw->restore();
        return back()->with('success', "キーワード「{$kw->label}」を復元しました。");
    }

    public function forceDeleteKeyword($id)
    {
        $kw = ReplyKeyword::onlyTrashed()->findOrFail($id);
        $label = $kw->label;
        $kw->forceDelete();
        return back()->with('success', "キーワード「{$label}」を完全に削除しました。");
    }

    /**
     * カテゴリ作成
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'business_type_id' => 'nullable|exists:business_types,id',
        ]);

        $maxOrder = ReplyCategory::max('sort_order') ?? 0;
        ReplyCategory::create([
            'name' => $validated['name'],
            'business_type_id' => !empty($validated['business_type_id']) ? $validated['business_type_id'] : null,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect('/admin/reply-categories')->with('success', 'カテゴリを追加しました。');
    }

    /**
     * カテゴリ更新
     */
    public function updateCategory(Request $request, ReplyCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'business_type_id' => 'nullable|exists:business_types,id',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update([
            'name' => $validated['name'],
            'business_type_id' => !empty($validated['business_type_id']) ? $validated['business_type_id'] : null,
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->has('is_active'),
        ]);

        return redirect('/admin/reply-categories')->with('success', 'カテゴリを更新しました。');
    }

    /**
     * カテゴリ削除
     */
    public function destroyCategory(ReplyCategory $category)
    {
        $category->delete();
        return redirect('/admin/reply-categories')->with('success', 'カテゴリを削除しました。');
    }

    /**
     * キーワード作成
     */
    public function storeKeyword(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:reply_categories,id',
            'label' => 'required|string|max:255',
            'keyword' => 'required|string|max:255',
        ]);

        $maxOrder = ReplyKeyword::where('category_id', $validated['category_id'])->max('sort_order') ?? 0;
        ReplyKeyword::create([
            'category_id' => $validated['category_id'],
            'label' => $validated['label'],
            'keyword' => $validated['keyword'],
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect('/admin/reply-categories')->with('success', 'キーワードを追加しました。');
    }

    /**
     * キーワード更新
     */
    public function updateKeyword(Request $request, ReplyKeyword $keyword)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'keyword' => 'required|string|max:255',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $keyword->update([
            'label' => $validated['label'],
            'keyword' => $validated['keyword'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->has('is_active'),
        ]);

        return redirect('/admin/reply-categories')->with('success', 'キーワードを更新しました。');
    }

    /**
     * キーワード削除
     */
    public function destroyKeyword(ReplyKeyword $keyword)
    {
        $keyword->delete();
        return redirect('/admin/reply-categories')->with('success', 'キーワードを削除しました。');
    }

    /**
     * AI で返信カテゴリ＋キーワードを生成
     * MEO対策として返信文に含めたい語句を業種に応じて生成
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

        $prompt = "業種『{$industry}』のお店の Google レビュー返信に使う『返信カテゴリ』とその中の『キーワード』を生成してください。MEO対策（地域SEO）として返信文に含めたい語句を整理する目的です。\n\n"
            . "■カテゴリ：3〜5 個（例：『取扱商品』『サービス内容』『立地・アクセス』『店舗特徴』）\n"
            . "■各カテゴリのキーワード：4〜8 個。それぞれに以下を含める：\n"
            . "  - label: 短い表示名（最大 30 文字、画面に表示するチップ名）\n"
            . "  - keyword: AI 返信生成時にプロンプトに渡す語句（最大 50 文字、複数語可）\n\n"
            . "【出力】JSON のみ。例：\n"
            . '{"categories":[{"name":"取扱商品","keywords":[{"label":"金","keyword":"金 買取 査定"}]}]}';

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
                $keywords = [];
                foreach (($cat['keywords'] ?? []) as $k) {
                    if (empty($k['label']) || empty($k['keyword'])) continue;
                    $keywords[] = [
                        'label' => mb_substr(trim($k['label']), 0, 100),
                        'keyword' => mb_substr(trim($k['keyword']), 0, 200),
                    ];
                }
                if (empty($keywords)) continue;
                $clean[] = [
                    'name' => mb_substr(trim($cat['name']), 0, 100),
                    'keywords' => $keywords,
                ];
            }

            return response()->json(['success' => true, 'data' => ['categories' => $clean]]);
        } catch (\Exception $e) {
            \Log::error('ReplyCategory aiSuggest failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'AI 生成に失敗しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * AI で生成したカテゴリ＋キーワードを一括保存
     */
    public function aiApply(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array|min:1',
            'categories.*.name' => 'required|string|max:100',
            'categories.*.keywords' => 'required|array|min:1',
            'categories.*.keywords.*.label' => 'required|string|max:255',
            'categories.*.keywords.*.keyword' => 'required|string|max:255',
        ]);

        $maxCatOrder = ReplyCategory::max('sort_order') ?? 0;
        $createdCategories = 0;
        $createdKeywords = 0;

        foreach ($validated['categories'] as $cat) {
            $maxCatOrder++;
            $category = ReplyCategory::create([
                'name' => $cat['name'],
                'sort_order' => $maxCatOrder,
                'is_active' => true,
            ]);
            $createdCategories++;

            $kwOrder = 0;
            foreach ($cat['keywords'] as $k) {
                $kwOrder++;
                ReplyKeyword::create([
                    'category_id' => $category->id,
                    'label' => $k['label'],
                    'keyword' => $k['keyword'],
                    'sort_order' => $kwOrder,
                    'is_active' => true,
                ]);
                $createdKeywords++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "カテゴリ {$createdCategories} 件、キーワード {$createdKeywords} 件を追加しました。",
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
