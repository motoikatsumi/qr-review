<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreWordPress;
use App\Services\TenantWordPressApi;
use App\Services\WordPressInstallerService;
use Illuminate\Http\Request;

class AutoWordPressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 店舗の自動 WP セットアップ状況を取得（AJAX）
     */
    public function status(Store $store)
    {
        $wp = StoreWordPress::where('store_id', $store->id)->first();
        if (!$wp) {
            return response()->json(['status' => 'not_installed']);
        }
        return response()->json([
            'status' => $wp->status,
            'site_url' => $wp->site_url,
            'admin_url' => $wp->admin_url,
            'admin_username' => $wp->admin_username,
            'admin_password' => $wp->admin_password,
            'installed_at' => $wp->installed_at?->toDateTimeString(),
            'connected_services' => $wp->connected_services ?? [],
            'last_error' => $wp->last_error,
        ]);
    }

    /**
     * 自動セットアップを実行
     */
    public function install(Request $request, Store $store, WordPressInstallerService $installer)
    {
        try {
            $wp = $installer->install($store);
            return response()->json([
                'success' => true,
                'site_url' => $wp->site_url,
                'admin_url' => $wp->admin_url,
                'admin_username' => $wp->admin_username,
                'admin_password' => $wp->admin_password,
                'message' => 'WordPress を自動セットアップしました。次に「FB/IG 連携」ボタンを押して接続してください。',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Auto WP install failed', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'セットアップに失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Jetpack 接続状況（FB/IG）を取得
     */
    public function jetpackStatus(Store $store, TenantWordPressApi $api)
    {
        $wp = StoreWordPress::where('store_id', $store->id)->first();
        if (!$wp || !$wp->isReady()) {
            return response()->json(['success' => false, 'error' => 'WP がセットアップされていません'], 404);
        }
        $status = $api->getJetpackStatus($wp);
        return response()->json([
            'success' => true,
            'jetpack_active' => $status['jetpack_active'],
            'jetpack_connected' => $status['jetpack_connected'],
            'connections' => $status['publicize_connections'],
            'error' => $status['error'],
        ]);
    }

    /**
     * 自動ログイン用フォーム（HTML 返却、JS が新タブで開いて submit する）
     * 認証情報は管理画面ログイン中の admin にしか露出しない
     */
    public function loginRedirect(Request $request, Store $store)
    {
        $wp = StoreWordPress::where('store_id', $store->id)->first();
        if (!$wp || !$wp->isReady()) {
            abort(404);
        }
        $dest = $request->input('dest', 'jetpack');
        $allowedDest = ['jetpack' => 'admin.php?page=jetpack#/sharing', 'admin' => 'index.php'];
        $redirectTo = $wp->site_url . '/wp-admin/' . ($allowedDest[$dest] ?? $allowedDest['jetpack']);

        $loginUrl = $wp->site_url . '/wp-login.php';
        $log = htmlspecialchars($wp->admin_username, ENT_QUOTES, 'UTF-8');
        $pwd = htmlspecialchars($wp->admin_password, ENT_QUOTES, 'UTF-8');
        $redir = htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8');
        $loginUrlEsc = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>WordPress に自動ログイン中…</title>
<style>body{font-family:sans-serif;text-align:center;padding:40px;color:#555;}</style>
</head><body>
<p>⏳ WordPress に自動ログイン中…</p>
<form id="autoLogin" method="POST" action="{$loginUrlEsc}">
    <input type="hidden" name="log" value="{$log}">
    <input type="hidden" name="pwd" value="{$pwd}">
    <input type="hidden" name="redirect_to" value="{$redir}">
    <input type="hidden" name="testcookie" value="1">
</form>
<script>
document.cookie = "wordpress_test_cookie=WP%20Cookie%20check; path=/";
document.getElementById('autoLogin').submit();
</script>
</body></html>
HTML;
        return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * セットアップ済み WP を削除
     */
    public function destroy(Store $store, WordPressInstallerService $installer)
    {
        $wp = StoreWordPress::where('store_id', $store->id)->first();
        if (!$wp) {
            return response()->json(['success' => false, 'error' => 'WP がセットアップされていません'], 404);
        }
        try {
            $installer->uninstall($wp);
            return response()->json(['success' => true, 'message' => 'WP を削除しました。']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
