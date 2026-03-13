<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $stores = Store::withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('id', 'asc')
            ->get();
        return view('admin.stores.index', compact('stores'));
    }

    public function create()
    {
        return view('admin.stores.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'google_review_url' => 'required|url|max:500',
            'ludocid' => 'nullable|string|max:30',
            'meo_keywords' => 'nullable|string|max:1000',
            'meo_ratio' => 'required|integer|min:0|max:100',
            'notify_email' => 'required|email|max:255',
            'slug' => 'nullable|string|max:100|unique:stores,slug',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']) ?: Str::random(8);
        }

        // スラッグの重複チェック
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Store::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter++;
        }

        Store::create($validated);

        return redirect('/admin/stores')->with('success', '店舗を追加しました。');
    }

    public function edit(Store $store)
    {
        return view('admin.stores.edit', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'google_review_url' => 'required|url|max:500',
            'ludocid' => 'nullable|string|max:30',
            'meo_keywords' => 'nullable|string|max:1000',
            'meo_ratio' => 'required|integer|min:0|max:100',
            'notify_email' => 'required|email|max:255',
            'slug' => 'required|string|max:100|unique:stores,slug,' . $store->id,
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $store->update($validated);

        return redirect('/admin/stores')->with('success', '店舗情報を更新しました。');
    }

    public function destroy(Store $store)
    {
        $store->delete();
        return redirect('/admin/stores')->with('success', '店舗を削除しました。');
    }
}
