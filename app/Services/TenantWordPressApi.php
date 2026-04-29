<?php

namespace App\Services;

use App\Models\StoreWordPress;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 自動構築した店舗 WP の REST API を Laravel 側からプロキシ呼び出しするためのサービス
 */
class TenantWordPressApi
{
    /**
     * Jetpack の接続状況（FB / IG など）を取得
     * 戻り値:
     *  - jetpack_active: bool         （Jetpack 自体が起動しているか）
     *  - jetpack_connected: bool      （Jetpack が WordPress.com に接続済みか）
     *  - publicize_connections: array （FB, IG 等の連携リスト）
     *  - error: string|null
     */
    public function getJetpackStatus(StoreWordPress $wp): array
    {
        $result = [
            'jetpack_active' => false,
            'jetpack_connected' => false,
            'publicize_connections' => [],
            'error' => null,
        ];

        $token = $wp->app_password;
        if (!$token) {
            $result['error'] = 'API トークンが未発行です。WordPress を再セットアップしてください。';
            return $result;
        }

        try {
            // mu-plugin が提供するカスタムエンドポイントを呼び出す
            $resp = $this->call($wp, 'GET', '/qrreview/v1/status');
            if (!$resp->successful()) {
                $result['error'] = 'WP REST API 呼出失敗: HTTP ' . $resp->status();
                return $result;
            }
            $data = $resp->json() ?: [];
            $result['jetpack_active']        = (bool) ($data['jetpack_active'] ?? false);
            $result['jetpack_connected']     = (bool) ($data['jetpack_connected'] ?? false);
            $result['publicize_connections'] = $data['connections'] ?? [];
        } catch (\Throwable $e) {
            $result['error'] = '状態取得に失敗: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * 任意の REST 呼出（Bearer + クエリ両方でトークン送信）
     */
    public function call(StoreWordPress $wp, string $method, string $path, array $body = [])
    {
        $url = rtrim($wp->site_url, '/') . '/wp-json' . $path;
        $token = $wp->app_password;

        // クエリでもトークンを渡す（Apache が Authorization ヘッダを剥ぐ環境用）
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'qr_token=' . urlencode($token);

        $req = Http::timeout(15)->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
        if (!empty($body)) {
            $req = $req->withHeaders(['Content-Type' => 'application/json']);
        }
        return $req->{strtolower($method)}($url, $body);
    }
}
