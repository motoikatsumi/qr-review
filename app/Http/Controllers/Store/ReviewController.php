<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    private function getStore()
    {
        $user = Auth::user();
        return $user->isAdmin() ? null : $user->store;
    }

    public function index(Request $request)
    {
        $store = $this->getStore();
        if (!$store) return redirect('/admin/stores');

        $query = Review::where('store_id', $store->id)->orderByDesc('created_at');

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->paginate(20);
        return view('store.reviews.index', compact('store', 'reviews'));
    }
}
