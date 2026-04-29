<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Http\Request;

/**
 * 顧客（テナント）が自社の請求書を閲覧するためのコントローラー
 * 運営管理用とは別。発行・編集・削除はできない（閲覧と印刷のみ）
 */
class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $tenant = Tenant::current();
        if (!$tenant) {
            return view('admin.invoices.index', [
                'invoices' => collect(),
                'tenant' => null,
                'error' => 'テナント情報を特定できませんでした。',
                'stats' => ['total' => 0, 'unpaid' => 0, 'unpaid_amount' => 0],
            ]);
        }

        // テナントには「下書き」は見せない（運営側の作業中の請求書を隠すため）
        $query = Invoice::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'draft');
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        $invoices = $query->orderByDesc('issue_date')->orderByDesc('id')->paginate(30);

        $stats = [
            'total' => Invoice::where('tenant_id', $tenant->id)->where('status', '!=', 'draft')->count(),
            'unpaid' => Invoice::where('tenant_id', $tenant->id)->whereIn('status', ['sent', 'overdue'])->count(),
            'unpaid_amount' => Invoice::where('tenant_id', $tenant->id)->whereIn('status', ['sent', 'overdue'])->sum('total_amount'),
        ];

        return view('admin.invoices.index', compact('invoices', 'tenant', 'stats'));
    }

    public function show(Invoice $invoice)
    {
        $tenant = Tenant::current();
        if (!$tenant || $invoice->tenant_id !== $tenant->id) {
            abort(404);
        }
        // 下書き状態の請求書はテナントに表示しない
        if ($invoice->status === 'draft') {
            abort(404);
        }
        $invoice->load(['tenant', 'items']);
        return view('admin.invoices.show', compact('invoice'));
    }

    public function print(Invoice $invoice)
    {
        $tenant = Tenant::current();
        if (!$tenant || $invoice->tenant_id !== $tenant->id) {
            abort(404);
        }
        if ($invoice->status === 'draft') {
            abort(404);
        }
        $invoice->load(['tenant', 'items']);
        return view('admin.invoices.print', compact('invoice'));
    }
}
