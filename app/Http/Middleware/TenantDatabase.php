<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TenantDatabase
{
    /**
     * サブドメインからテナントDBを特定し、接続先を切り替える
     */
    public function handle(Request $request, Closure $next)
    {
        // ローカル開発環境ではテナント解決をスキップ（デフォルトDB使用）
        if (app()->isLocal()) {
            return $next($request);
        }

        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        // ローカル開発時はスキップ（localhost / 127.0.0.1）
        if (in_array($host, ['localhost', '127.0.0.1']) || str_contains($host, 'localhost')) {
            return $next($request);
        }

        // 明示的に単一テナント運用を指定された場合はスキップ
        if (env('MULTI_TENANT_ENABLED') === false || env('MULTI_TENANT_ENABLED') === '0' || env('MULTI_TENANT_ENABLED') === 'false') {
            return $next($request);
        }

        // master DBからテナント情報を取得
        // master DB に接続できない場合（単一テナント運用環境）は素通りでデフォルト DB を使用
        try {
            $tenant = DB::connection('master')
                ->table('tenants')
                ->where('subdomain', $subdomain)
                ->where('is_active', true)
                ->first();
        } catch (\Throwable $e) {
            // master DB 未設定 / アクセス権なし = 単一テナント運用とみなす
            return $next($request);
        }

        if (!$tenant) {
            // master DB に該当テナントが無い場合もデフォルト DB で動作させる
            // （マルチテナント運用前のレガシー or サブドメインが master 未登録）
            return $next($request);
        }

        // テナントDBに接続を切り替え
        Config::set('database.connections.mysql.database', $tenant->db_name);
        Config::set('database.connections.mysql.username', $tenant->db_username ?: env('DB_USERNAME'));
        Config::set('database.connections.mysql.password', $tenant->db_password ?: env('DB_PASSWORD'));

        DB::purge('mysql');
        DB::reconnect('mysql');

        // テナント情報をリクエストに保存（後続で利用可能に）
        $request->attributes->set('tenant', $tenant);
        app()->instance('current_tenant', $tenant);

        return $next($request);
    }
}
