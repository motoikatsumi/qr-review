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

        // リバースプロキシ経由の実際のクライアントIPを取得
        $clientIp = $request->ip();

        // X-Forwarded-For ヘッダがある場合は最初のIPを使用
        $forwarded = $request->header('X-Forwarded-For');
        if ($forwarded) {
            $ips = array_map('trim', explode(',', $forwarded));
            $clientIp = $ips[0];
        }

        // X-Real-IP ヘッダがある場合はそちらを優先
        $realIp = $request->header('X-Real-IP');
        if ($realIp) {
            $clientIp = trim($realIp);
        }

        if (!in_array($clientIp, $allowedIps)) {
            abort(403, 'アクセスが許可されていません。');
        }

        return $next($request);
    }
}
