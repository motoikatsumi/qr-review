<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SuggestionCategory;
use App\Models\SuggestionTheme;
use Illuminate\Http\Request;

class SuggestionThemeController extends Controller
{
    /**
     * カテゴリ・テーマ一覧
     */
    public function index()
    {
        $categories = SuggestionCategory::with(['themes' => function ($q) {
            $q->orderBy('sort_order');
        }])->orderBy('sort_order')->get();

        return view('admin.suggestion-themes.index', compact('categories'));
    }

    /**
     * カテゴリ作成
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $maxOrder = SuggestionCategory::max('sort_order') ?? 0;
        SuggestionCategory::create([
            'name' => $validated['name'],
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
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update([
            'name' => $validated['name'],
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
}
