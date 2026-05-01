<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Super Admin ルートのアクセス制御テスト。
 * 認証ガードが正しく機能していることを確認する。
 */
class SuperAdminAuthGateTest extends TestCase
{
    /**
     * @dataProvider superAdminUrlProvider
     */
    public function test_unauthenticated_redirects_to_super_admin_login(string $url): void
    {
        // ログイン状態をクリア
        Auth::guard('super_admin')->logout();

        $response = $this->get($url);
        $this->assertContains(
            $response->status(),
            [302, 401, 403],
            "未認証で {$url} にアクセスしたが {$response->status()} が返った"
        );
    }

    /**
     * @dataProvider superAdminUrlProvider
     */
    public function test_authenticated_super_admin_can_access(string $url): void
    {
        $sa = SuperAdmin::first();
        if (!$sa) {
            $this->markTestSkipped('SuperAdmin が未登録(master DB)');
        }
        Auth::guard('super_admin')->loginUsingId($sa->id);

        $response = $this->get($url);
        $this->assertContains(
            $response->status(),
            [200, 302],
            "super_admin 認証で {$url} → HTTP {$response->status()}"
        );
    }

    public function superAdminUrlProvider(): array
    {
        return [
            'ダッシュボード' => ['/super-admin/dashboard'],
            'テナント一覧'   => ['/super-admin/tenants'],
            '請求書一覧'     => ['/super-admin/invoices'],
            '一括発行フォーム' => ['/super-admin/invoices/bulk'],
            '運営マニュアル' => ['/super-admin/manual'],
        ];
    }

    /**
     * 通常 admin で /super-admin/manual を直接叩いてもアクセスできない
     */
    public function test_admin_user_cannot_access_super_admin_manual(): void
    {
        Auth::guard('super_admin')->logout();

        $admin = User::where('role', 'admin')->first();
        if (!$admin) $this->markTestSkipped('admin ユーザー無し');
        Auth::guard('web')->loginUsingId($admin->id);

        $response = $this->get('/super-admin/manual');
        $this->assertContains(
            $response->status(),
            [302, 401, 403],
            'admin 認証だけで /super-admin/manual に到達できてしまっている'
        );
    }
}
