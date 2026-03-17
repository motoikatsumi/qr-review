<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReplyCategory;
use App\Models\ReplyKeyword;
use Illuminate\Http\Request;

class ReplyCategoryController extends Controller
{
    /**
     * 返信カテゴリ・キーワード管理画面
     */
    public function index()
    {
        $categories = ReplyCategory::with(['keywords' => function ($q) {
            $q->orderBy('sort_order');
        }])->orderBy('sort_order')->get();

        return view('admin.reply-categories.index', compact('categories'));
    }

    /**
     * カテゴリ作成
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $maxOrder = ReplyCategory::max('sort_order') ?? 0;
        ReplyCategory::create([
            'name' => $validated['name'],
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
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update([
            'name' => $validated['name'],
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
}
