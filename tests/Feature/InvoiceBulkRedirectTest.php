<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * 請求書一括発行/支払済み一括処理が完了後に /super-admin/invoices にリダイレクトすることを確認。
 */
class InvoiceBulkRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $sa = SuperAdmin::first();
        if (!$sa) $this->markTestSkipped('SuperAdmin 無し');
        Auth::guard('super_admin')->loginUsingId($sa->id);
    }

    /**
     * バリデーションエラーで戻ってきた場合は /super-admin/invoices/bulk に redirect
     * (これは正常動作)
     */
    public function test_bulk_with_no_tenants_returns_validation_error(): void
    {
        $response = $this->post('/super-admin/invoices/bulk', [
            'period_year' => 2026,
            'period_month' => 4,
            'issue_date' => '2026-04-30',
            'due_preset' => 'next_month_end',
            'tax_rate' => 10,
            // tenant_ids を渡さない
        ]);
        // validation エラー: 422 か redirect 302
        $this->assertContains($response->status(), [302, 422]);
    }

    /**
     * 一括発行成功時に /super-admin/invoices にリダイレクト(回帰防止)
     */
    public function test_bulk_generate_redirects_to_invoices_index(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) $this->markTestSkipped('テナント無し');

        // 重複防止のため非常に未来の月で実行
        $response = $this->post('/super-admin/invoices/bulk', [
            'period_year' => 2099,
            'period_month' => 12,
            'issue_date' => '2099-12-31',
            'due_preset' => 'next_month_end',
            'tax_rate' => 10,
            'tenant_ids' => [$tenant->id],
        ]);

        $response->assertStatus(302);
        $this->assertStringEndsWith('/super-admin/invoices', $response->headers->get('Location'),
            '一括発行後に /super-admin/invoices にリダイレクトされていない');

        // テスト後始末: 発行された請求書を削除して副作用を残さない
        \App\Models\Invoice::whereYear('billing_period_start', 2099)->delete();
    }
}
