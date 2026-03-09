<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IpRestriction
{
    public function handle(Request $request, Closure $next)
    {
        $allowedIps = array_filter(array_map('trim', explode(',', env('ALLOWED_IPS', ''))));

        if (empty($allowedIps)) {
            return $next($request);
        }

        if (!in_array($request->ip(), $allowedIps)) {
            abort(403, 'アクセスが許可されていません。');
        }

        return $next($request);
    }
}
