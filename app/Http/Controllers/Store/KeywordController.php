<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ReplyCategory;
use App\Models\SuggestionCategory;
use Illuminate\Support\Facades\Auth;

class KeywordController extends Controller
{
    private function getStore()
    {
        $user = Auth::user();
        return $user->isAdmin() ? null : $user->store;
    }

    public function index()
    {
        $store = $this->getStore();
        if (!$store) return redirect('/admin/stores');

        $businessTypeId = $store->business_type_id;

        // 業種に紐付くカテゴリ + 業種未指定（共通）カテゴリ
        $suggestionCategories = SuggestionCategory::with('activeThemes')
            ->where(function ($q) use ($businessTypeId) {
                $q->where('business_type_id', $businessTypeId)->orWhereNull('business_type_id');
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $replyCategories = ReplyCategory::with('activeKeywords')
            ->where(function ($q) use ($businessTypeId) {
                $q->where('business_type_id', $businessTypeId)->orWhereNull('business_type_id');
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('store.settings.keywords', compact('store', 'suggestionCategories', 'replyCategories'));
    }
}
