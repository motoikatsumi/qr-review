<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Store;
use App\Services\GoogleBusinessService;
use Illuminate\Http\Request;

class GoogleSettingController extends Controller
{
    /**
     * Google連携設定画面
     */
    public function index(GoogleBusinessService $google)
    {
        $settings = [
            'client_id' => SiteSetting::get('google_client_id'),
            'client_secret' => SiteSetting::get('google_client_secret'),
            'account_id' => SiteSetting::get('google_account_id'),
            'is_connected' => $google->isConnected(),
        ];

        $stores = Store::where('is_active', true)->orderBy('name')->get();

        // 連携済みの場合、アカウント一覧とロケーション一覧を取得
        $accounts = [];
        $locations = [];
        if ($settings['is_connected'] && $settings['account_id']) {
            $locations = $google->listLocations($settings['account_id']) ?? [];
        }

        return view('admin.google-settings.index', compact('settings', 'stores', 'locations'));
    }

    /**
     * クライアントID・シークレットを保存
     */
    public function saveCredentials(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string|max:255',
        ]);

        SiteSetting::set('google_client_id', $validated['client_id']);
        SiteSetting::set('google_client_secret', $validated['client_secret']);

        return redirect('/admin/google-settings')->with('success', 'Google API認証情報を保存しました。');
    }

    /**
     * OAuth認可フローを開始
     */
    public function redirectToGoogle(GoogleBusinessService $google)
    {
        $redirectUri = url('/admin/google-settings/callback');
        $authUrl = $google->getAuthUrl($redirectUri);

        return redirect()->away($authUrl);
    }

    /**
     * OAuthコールバック
     */
    public function callback(Request $request, GoogleBusinessService $google)
    {
        if ($request->has('error')) {
            return redirect('/admin/google-settings')->with('error', 'Google認証がキャンセルされました。');
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect('/admin/google-settings')->with('error', '認証コードが取得できませんでした。');
        }

        $redirectUri = url('/admin/google-settings/callback');
        $success = $google->exchangeCode($code, $redirectUri);

        if (!$success) {
            return redirect('/admin/google-settings')->with('error', 'トークン取得に失敗しました。認証情報を確認してください。');
        }

        // アカウント一覧を取得して自動設定
        $accounts = $google->listAccounts();
        if ($accounts && count($accounts) > 0) {
            SiteSetting::set('google_account_id', $accounts[0]['name']);
        }

        return redirect('/admin/google-settings')->with('success', 'Googleアカウントと連携しました。');
    }

    /**
     * アカウントIDを選択・保存
     */
    public function saveAccount(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|string|max:255',
        ]);

        SiteSetting::set('google_account_id', $validated['account_id']);

        return redirect('/admin/google-settings')->with('success', 'アカウントを設定しました。');
    }

    /**
     * 店舗とGoogleロケーションの紐付けを保存
     */
    public function saveLocationMapping(Request $request)
    {
        $validated = $request->validate([
            'mappings' => 'required|array',
            'mappings.*.store_id' => 'required|exists:stores,id',
            'mappings.*.location_name' => 'nullable|string|max:255',
        ]);

        foreach ($validated['mappings'] as $mapping) {
            Store::where('id', $mapping['store_id'])->update([
                'google_location_name' => $mapping['location_name'] ?: null,
            ]);
        }

        return redirect('/admin/google-settings')->with('success', '店舗とGoogleロケーションの紐付けを保存しました。');
    }

    /**
     * 連携解除
     */
    public function disconnect()
    {
        SiteSetting::set('google_access_token', '');
        SiteSetting::set('google_refresh_token', '');
        SiteSetting::set('google_token_expires_at', '');
        SiteSetting::set('google_account_id', '');

        return redirect('/admin/google-settings')->with('success', 'Google連携を解除しました。');
    }
}
