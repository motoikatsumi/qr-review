<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * マニュアル画面の画像参照が壊れていないかをチェックするテスト。
 *
 * 過去の障害:
 *   super-admin/manual で manual-assets/... の相対パスを使ったため
 *   /super-admin/manual-assets/... に解決されて 404 になっていた。
 */
class ManualImagePathsTest extends TestCase
{
    public function test_general_manual_file_exists_and_references_assets(): void
    {
        $path = public_path('manual.html');
        $this->assertFileExists($path);
        $body = file_get_contents($path);
        $this->assertStringContainsString('manual-assets/images/', $body);
    }

    /**
     * 一般マニュアル(manual.html)からは Super Admin セクションが除外されていること
     */
    public function test_general_manual_does_not_contain_super_admin_section(): void
    {
        $body = file_get_contents(public_path('manual.html'));
        $this->assertStringNotContainsString('15. Super Admin', $body);
        $this->assertStringNotContainsString('id="super-admin-about"', $body);
    }

    /**
     * Super Admin マニュアルが super_admin guard で保護されていて、
     * 認証済み時に画像パスがすべて絶対パス(/manual-assets/...)になっていること
     */
    public function test_super_admin_manual_uses_absolute_image_paths(): void
    {
        $sa = SuperAdmin::first();
        if (!$sa) {
            $this->markTestSkipped('SuperAdmin が未登録');
        }
        Auth::guard('super_admin')->loginUsingId($sa->id);

        $response = $this->get('/super-admin/manual');
        $response->assertStatus(200);

        $body = $response->getContent();

        // 相対パス manual-assets/... が無いこと(/manual-assets/... の絶対形だけ)
        // 「src="manual-assets/」のようなパターンを禁止する
        $this->assertDoesNotMatchRegularExpression(
            '/(src|href)="manual-assets\//',
            $body,
            'Super Admin マニュアルに相対パス指定の画像が残っている'
        );

        // 絶対パスは存在すべき
        $this->assertMatchesRegularExpression(
            '/(src|href)="\/manual-assets\//',
            $body,
            'Super Admin マニュアルに絶対パスの画像参照が無い'
        );
    }

    /**
     * 主要なスクショファイルが public/manual-assets/images/ に実在すること
     */
    public function test_required_screenshot_files_exist(): void
    {
        $required = [
            '01-login.png',
            '02-dashboard.png',
            '03-stores-list.png',
            '07-business-types-list.png',
            '09-suggestion-themes.png',
            '11-reviews-list.png',
            '12-google-reviews.png',
            '13-purchase-posts-list.png',
            '19-super-admin-dashboard.png',
            '20-super-admin-tenants.png',
        ];
        $base = public_path('manual-assets/images');
        foreach ($required as $f) {
            $this->assertFileExists("{$base}/{$f}", "スクショファイルが存在しない: {$f}");
        }
    }
}
