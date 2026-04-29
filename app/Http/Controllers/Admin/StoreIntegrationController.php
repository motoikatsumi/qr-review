<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreIntegration;
use App\Services\WordPressService;
use Illuminate\Http\Request;

class StoreIntegrationController extends Controller
{
    /**
     * 店舗の外部連携一覧
     */
    public function index(Store $store)
    {
        return redirect("/admin/stores/{$store->id}/edit?tab=integrations");
    }

    /**
     * WordPress連携を保存（URL + ユーザー名 + アプリパスワード）
     */
    public function saveWordPress(Request $request, Store $store)
    {
        $validated = $request->validate([
            'wp_url'      => 'required|url',
            'wp_username' => 'required|string|max:255',
            'wp_password' => 'required|string',
        ]);

        $wp = new WordPressService();
        $wp->setCredentials(
            rtrim($validated['wp_url'], '/'),
            $validated['wp_username'],
            $validated['wp_password']
        );
        $test = $wp->testConnection();
        if (!$test['success']) {
            return redirect("/admin/stores/{$store->id}/edit?tab=integrations")->with('error', 'WordPress接続エラー: ' . $test['error']);
        }

        StoreIntegration::updateOrCreate(
            ['store_id' => $store->id, 'service' => 'wordpress'],
            [
                'access_token' => $validated['wp_password'],
                'extra_data'   => [
                    'wp_url'      => rtrim($validated['wp_url'], '/'),
                    'wp_username' => $validated['wp_username'],
                ],
                'is_active'    => true,
            ]
        );

        return redirect("/admin/stores/{$store->id}/edit?tab=integrations")->with('success', 'WordPress連携を保存しました（' . ($test['user'] ?? '') . '）。');
    }

    /**
     * 連携を解除
     */
    public function destroy(Store $store, string $service)
    {
        StoreIntegration::where('store_id', $store->id)
            ->where('service', $service)
            ->delete();

        return redirect("/admin/stores/{$store->id}/edit?tab=integrations")->with('success', ucfirst($service) . '連携を解除しました。');
    }
}
