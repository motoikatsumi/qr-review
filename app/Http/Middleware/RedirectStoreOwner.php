<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectStoreOwner
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->isStoreOwner()) {
            return redirect('/store/dashboard');
        }

        return $next($request);
    }
}
