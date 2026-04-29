<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\StoreIntegration;
use App\Services\WordPressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IntegrationController extends Controller
{
    private function getStore()
    {
        $user = Auth::user();
        return $user->isAdmin() ? null : $user->store;
    }

    public function index()
    {
        $store = $this->getStore();
        if (!$store) return redirect('/admin/stores');

        $integrations = $store->integrations()->get()->keyBy('service');
        return view('store.settings.integrations', compact('store', 'integrations'));
    }

    public function saveWordPress(Request $request)
    {
        $store = $this->getStore();
        if (!$store) return redirect('/admin/stores');

        $validated = $request->validate([
            'wp_url'      => 'required|url',
            'wp_username' => 'required|string|max:255',
            'wp_password' => 'required|string',
        ]);

        $wp = new WordPressService();
        $wp->setCredentials(rtrim($validated['wp_url'], '/'), $validated['wp_username'], $validated['wp_password']);
        $test = $wp->testConnection();
        if (!$test['success']) {
            return back()->with('error', 'WordPress接続エラー: ' . $test['error']);
        }

        StoreIntegration::updateOrCreate(
            ['store_id' => $store->id, 'service' => 'wordpress'],
            [
                'access_token' => $validated['wp_password'],
                'extra_data'   => ['wp_url' => rtrim($validated['wp_url'], '/'), 'wp_username' => $validated['wp_username']],
                'is_active'    => true,
            ]
        );

        return back()->with('success', 'WordPress連携を保存しました。');
    }

    public function destroy(string $service)
    {
        $store = $this->getStore();
        if (!$store) return redirect('/admin/stores');

        StoreIntegration::where('store_id', $store->id)->where('service', $service)->delete();
        return back()->with('success', ucfirst($service) . '連携を解除しました。');
    }
}
