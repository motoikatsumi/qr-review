<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $showTrashed = $request->input('show') === 'trashed';
        $query = $showTrashed ? Store::onlyTrashed() : Store::query();
        $stores = $query->withCount(['reviews', 'googleReviews'])
            ->withAvg('reviews', 'rating')
            ->orderBy('id', 'asc')
            ->get();
        $trashedCount = Store::onlyTrashed()->count();
        return view('admin.stores.index', compact('stores', 'showTrashed', 'trashedCount'));
    }

    public function restore($id)
    {
        $store = Store::onlyTrashed()->findOrFail($id);
        $store->restore();
        return back()->with('success', "店舗「{$store->name}」を復元しました。");
    }

    public function forceDelete($id)
    {
        $store = Store::onlyTrashed()->findOrFail($id);
        $name = $store->name;
        $store->forceDelete();
        return back()->with('success', "店舗「{$name}」を完全に削除しました。");
    }

    public function create()
    {
        $businessTypes = BusinessType::active()->orderBy('sort_order')->get();
        return view('admin.stores.create', compact('businessTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'google_review_url'     => 'required|url|max:500',
            'notify_email'          => 'required|email|max:255',
            'notify_threshold'      => 'nullable|integer|min:1|max:4',
            'slug'                  => 'nullable|string|max:100|unique:stores,slug',
            'business_type_id'      => 'nullable|exists:business_types,id',
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
        $businessTypes = BusinessType::active()->orderBy('sort_order')->get();
        $store->load(['businessType', 'postTemplate']);
        $integrations = $store->integrations()->get()->keyBy('service');
        $activeTab = request('tab', 'basic');
        $autoWp = \App\Models\StoreWordPress::where('store_id', $store->id)->first();
        return view('admin.stores.edit', compact('store', 'businessTypes', 'integrations', 'activeTab', 'autoWp'));
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'google_review_url'     => 'required|url|max:500',
            'notify_email'          => 'required|email|max:255',
            'notify_threshold'      => 'nullable|integer|min:1|max:4',
            'slug'                  => 'required|string|max:100|unique:stores,slug,' . $store->id,
            'is_active'             => 'boolean',
            'use_wordpress'         => 'boolean',
            'business_type_id'      => 'nullable|exists:business_types,id',
        ]);

        $validated['is_active']     = $request->boolean('is_active');
        $validated['use_wordpress'] = $request->boolean('use_wordpress');

        $store->update($validated);

        return redirect('/admin/stores')->with('success', '店舗情報を更新しました。');
    }

    public function destroy(Store $store)
    {
        $store->delete();
        return redirect('/admin/stores')->with('success', '店舗を削除しました。');
    }

    /**
     * 店舗設定の複製
     * 多店舗運営の会社が、ある店舗の AI 設定・業種・通知先などをコピーして
     * 新しい店舗を素早く作るための機能
     */
    public function duplicate(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'google_review_url' => 'nullable|url|max:500',
            'notify_email' => 'nullable|email|max:255',
        ]);

        $copyable = collect($store->getAttributes())
            ->except(['id', 'created_at', 'updated_at', 'slug', 'name', 'google_review_url', 'ludocid', 'google_location_name'])
            ->toArray();

        $copyable['name'] = $validated['name'];
        $copyable['google_review_url'] = $validated['google_review_url'] ?? '';
        $copyable['notify_email'] = $validated['notify_email'] ?? $store->notify_email;
        $copyable['is_active'] = true;

        // slug を自動生成
        $base = Str::slug($validated['name']) ?: Str::random(8);
        $slug = $base;
        $counter = 1;
        while (Store::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }
        $copyable['slug'] = $slug;

        $newStore = Store::create($copyable);

        // 投稿フッターテンプレートをコピー
        if ($store->postTemplate) {
            $newStore->postTemplate()->create([
                'template_text' => $store->postTemplate->template_text,
            ]);
        }

        return redirect("/admin/stores/{$newStore->id}/edit")
            ->with('success', "店舗「{$store->name}」の設定をコピーして新しい店舗「{$newStore->name}」を作成しました。Google レビュー URL など店舗固有の情報を編集してください。");
    }
}
