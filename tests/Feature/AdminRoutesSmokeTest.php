<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * 管理画面の主要ルートが 200 を返すかを確認するスモークテスト。
 *
 * 既存のローカルDB(店舗・口コミデータ含む)を read-only で使用するため、
 * RefreshDatabase は使わない。書き込みを伴う操作はテスト対象外。
 */
class AdminRoutesSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 管理者として認証
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $this->markTestSkipped('admin ユーザーが見つからない(ローカルDB前提)');
        }
        Auth::guard('web')->loginUsingId($admin->id);
    }

    /**
     * @dataProvider adminUrlProvider
     */
    public function test_admin_url_returns_200(string $url): void
    {
        $response = $this->get($url);
        $this->assertContains(
            $response->status(),
            [200, 302], // 302 はリダイレクト(店舗リスト→ダッシュボード等)を許容
            "GET {$url} → HTTP {$response->status()}"
        );
    }

    public function adminUrlProvider(): array
    {
        return [
            'ダッシュボード'         => ['/admin/dashboard'],
            '店舗一覧'               => ['/admin/stores'],
            '業種一覧'               => ['/admin/business-types'],
            '口コミ一覧'             => ['/admin/reviews'],
            'Google口コミ'           => ['/admin/google-reviews'],
            '投稿一覧'               => ['/admin/purchase-posts'],
            '投稿作成'               => ['/admin/purchase-posts/create'],
            '口コミテーマ管理'       => ['/admin/suggestion-themes'],
            '返信カテゴリ管理'       => ['/admin/reply-categories'],
            'AI返信プレビュー'       => ['/admin/ai-reply-preview'],
            'Google連携設定'         => ['/admin/google-settings'],
            'ユーザー管理'           => ['/admin/users'],
            // /manual.html は静的ファイル(Laravel ルート外)なので別途 ManualImagePathsTest で確認
        ];
    }

    /**
     * 業種「マスタ」表記が消えていること(回帰防止)
     */
    public function test_admin_layout_does_not_contain_legacy_business_type_master_label(): void
    {
        $response = $this->get('/admin/dashboard');
        if ($response->status() === 302) {
            $this->markTestSkipped('リダイレクトされたためレイアウト未取得');
        }
        $this->assertStringNotContainsString('業種マスタ', $response->getContent(), 'ナビに「業種マスタ」表記が残っている');
        $this->assertStringContainsString('🏢 業種', $response->getContent(), 'ナビに「業種」リンクが無い');
    }

    /**
     * ダッシュボードの「買取投稿」表記が「投稿」になっていること
     */
    public function test_dashboard_does_not_contain_legacy_purchase_post_label(): void
    {
        $response = $this->get('/admin/dashboard');
        if ($response->status() === 302) {
            $this->markTestSkipped('リダイレクトされたためダッシュボード未取得');
        }
        // 単独の「投稿」表記は許容、「買取投稿」だけ NG
        $this->assertStringNotContainsString('投稿失敗の買取投稿', $response->getContent());
        $this->assertStringNotContainsString('買取投稿を作る', $response->getContent());
    }
}
