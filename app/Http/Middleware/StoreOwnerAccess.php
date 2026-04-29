<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StoreOwnerAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect('/admin/login');
        }

        // 管理者はstore画面もアクセス可能
        if ($user->isAdmin()) {
            return $next($request);
        }

        // store_ownerロールのみ許可
        if (!$user->isStoreOwner()) {
            abort(403, 'このページにアクセスする権限がありません。');
        }

        // 自分の店舗が設定されているか確認
        if (!$user->store_id) {
            abort(403, '担当店舗が設定されていません。管理者に連絡してください。');
        }

        return $next($request);
    }
}
