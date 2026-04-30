<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * 請求書一覧（フィルタ付き）
     */
    public function index(Request $request)
    {
        $query = Invoice::with('tenant');

        if ($request->filled('tenant')) {
            $query->where('tenant_id', $request->input('tenant'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('month')) {
            $month = Carbon::createFromFormat('Y-m', $request->input('month'));
            $query->whereYear('billing_period_start', $month->year)
                ->whereMonth('billing_period_start', $month->month);
        }

        $invoices = $query->orderByDesc('invoice_number')->orderByDesc('id')->paginate(50);
        $tenants = Tenant::orderBy('company_name')->get();

        // 集計
        $stats = [
            'total_count' => Invoice::count(),
            'total_amount' => Invoice::sum('total_amount'),
            'unpaid_count' => Invoice::whereIn('status', ['sent', 'overdue'])->count(),
            'unpaid_amount' => Invoice::whereIn('status', ['sent', 'overdue'])->sum('total_amount'),
            'paid_count' => Invoice::where('status', 'paid')->count(),
            'paid_amount' => Invoice::where('status', 'paid')->sum('total_amount'),
            'this_month_paid_count' => Invoice::where('status', 'paid')
                ->whereYear('paid_date', now()->year)->whereMonth('paid_date', now()->month)->count(),
            'this_month_paid_amount' => Invoice::where('status', 'paid')
                ->whereYear('paid_date', now()->year)->whereMonth('paid_date', now()->month)->sum('total_amount'),
        ];

        return view('super-admin.invoices.index', compact('invoices', 'tenants', 'stats'));
    }

    /**
     * 個別発行画面（テナント選択 → 確認 → 発行）
     */
    public function create(Request $request)
    {
        $tenants = Tenant::where('is_active', true)->orderBy('company_name')->get();
        $selectedTenant = null;
        $stores = [];
        if ($request->filled('tenant_id')) {
            $selectedTenant = Tenant::find($request->input('tenant_id'));
            if ($selectedTenant) {
                $stores = $this->fetchStoresForTenant($selectedTenant);
            }
        }
        return view('super-admin.invoices.create', compact('tenants', 'selectedTenant', 'stores'));
    }

    /**
     * 個別発行
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:master.tenants,id',
            'billing_period_start' => 'required|date',
            'billing_period_end' => 'required|date|after_or_equal:billing_period_start',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:draft,sent',
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
        $invoice = $this->createInvoiceForTenant($tenant, [
            'billing_period_start' => $validated['billing_period_start'],
            'billing_period_end' => $validated['billing_period_end'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'tax_rate' => $validated['tax_rate'],
            'notes' => $validated['notes'] ?? null,
            'items' => $validated['items'],
            'status' => $validated['status'] ?? 'draft',
        ]);

        $msg = $invoice->status === 'sent'
            ? '請求書を発行しました（テナントに公開）。'
            : '請求書を下書き保存しました。発行するまでテナントには表示されません。';
        return redirect("/super-admin/invoices/{$invoice->id}")->with('success', $msg);
    }

    /**
     * 請求書詳細（印刷用ビュー）
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['tenant', 'items']);
        return view('super-admin.invoices.show', compact('invoice'));
    }

    /**
     * 印刷専用ビュー（ヘッダ・フッタなし）
     */
    public function print(Invoice $invoice)
    {
        $invoice->load(['tenant', 'items']);
        return view('super-admin.invoices.print', compact('invoice'));
    }

    /**
     * ステータス更新（入金済み等）
     */
    public function updateStatus(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'paid_date' => 'required_if:status,paid|nullable|date',
        ], [
            'paid_date.required_if' => '入金済みにする場合は入金日を入力してください。',
        ]);

        // status=paid 以外に変更したら paid_date は自動でクリア（整合性維持）
        if ($validated['status'] !== 'paid') {
            $validated['paid_date'] = null;
        }

        $invoice->update($validated);
        return redirect('/super-admin/invoices')->with('success', "請求書「{$invoice->invoice_number}」を更新しました。");
    }

    /**
     * 削除
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->items()->delete();
        $invoice->delete();
        return redirect('/super-admin/invoices')->with('success', '請求書を削除しました。');
    }

    /**
     * 一括入金処理：選択された請求書を指定日付で入金済みに変更
     */
    public function bulkMarkPaid(Request $request)
    {
        $validated = $request->validate([
            'invoice_ids'   => 'required|array|min:1',
            'invoice_ids.*' => 'integer',
            'paid_date'     => 'required|date',
        ], [
            'invoice_ids.required' => '請求書を1件以上選択してください。',
            'paid_date.required'   => '入金日を指定してください。',
        ]);

        // 安全のため、未入金のもの（sent/overdue）のみを対象とする
        $count = Invoice::whereIn('id', $validated['invoice_ids'])
            ->whereIn('status', ['sent', 'overdue'])
            ->update([
                'status'    => 'paid',
                'paid_date' => $validated['paid_date'],
            ]);

        $skipped = count($validated['invoice_ids']) - $count;
        $msg = "{$count} 件を入金済みに更新しました（入金日: {$validated['paid_date']}）";
        if ($skipped > 0) {
            $msg .= "。{$skipped} 件は対象外（既に入金済み・下書き等）のためスキップしました。";
        }
        return redirect('/super-admin/invoices')->with('success', $msg);
    }

    /**
     * 月次一括発行画面
     */
    public function bulkForm()
    {
        $tenants = Tenant::where('is_active', true)->orderBy('company_name')->get();
        // 各テナントの店舗数 + 月額料金を計算
        foreach ($tenants as $t) {
            $stores = $this->fetchStoresForTenant($t);
            $t->_store_count = count($stores);
            $t->_stores = $stores;
            $t->_monthly_fee = $t->calculateMonthlyFee($t->_store_count);
            $t->_already_invoiced = Invoice::where('tenant_id', $t->id)
                ->whereYear('billing_period_start', now()->year)
                ->whereMonth('billing_period_start', now()->month)
                ->exists();
        }
        return view('super-admin.invoices.bulk', compact('tenants'));
    }

    /**
     * 月次一括発行（実行）
     */
    public function bulkGenerate(Request $request)
    {
        $validated = $request->validate([
            'period_year' => 'required|integer|min:2020|max:2100',
            'period_month' => 'required|integer|min:1|max:12',
            'issue_date' => 'required|date',
            'due_preset' => 'nullable|in:this_month_end,next_month_end,next_next_month_end,days_15,days_30,days_60',
            'due_days' => 'nullable|integer|min:0|max:90', // 旧バージョン互換
            'tax_rate' => 'required|numeric|min:0|max:100',
            'tenant_ids' => 'required|array|min:1',
            'tenant_ids.*' => 'integer|exists:master.tenants,id',
        ]);

        $period = Carbon::createFromDate($validated['period_year'], $validated['period_month'], 1);
        $issue = Carbon::parse($validated['issue_date']);

        // 支払期限の決定: due_preset があればそれで、無ければ due_days(旧)、両方無ければ翌月末
        $due = match ($validated['due_preset'] ?? null) {
            'this_month_end'      => $issue->copy()->endOfMonth(),
            'next_month_end'      => $issue->copy()->addMonthNoOverflow()->endOfMonth(),
            'next_next_month_end' => $issue->copy()->addMonthsNoOverflow(2)->endOfMonth(),
            'days_15'             => $issue->copy()->addDays(15),
            'days_30'             => $issue->copy()->addDays(30),
            'days_60'             => $issue->copy()->addDays(60),
            default               => isset($validated['due_days'])
                ? $issue->copy()->addDays($validated['due_days'])
                : $issue->copy()->addMonthNoOverflow()->endOfMonth(),
        };

        $created = 0;
        $skipped = 0;
        foreach ($validated['tenant_ids'] as $tenantId) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) continue;

            // 既に発行済みならスキップ
            $exists = Invoice::where('tenant_id', $tenant->id)
                ->whereYear('billing_period_start', $period->year)
                ->whereMonth('billing_period_start', $period->month)
                ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            $stores = $this->fetchStoresForTenant($tenant);

            // 明細生成（店舗ごとに 1 行 or 固定料金 1 行）
            $items = [];
            if (!is_null($tenant->monthly_fee_override) && $tenant->monthly_fee_override > 0) {
                $items[] = [
                    'description' => "QRレビュー月額利用料（{$period->format('Y/m')}）",
                    'quantity' => 1,
                    'unit_price' => (int) $tenant->monthly_fee_override,
                ];
            } else {
                $perStore = (int) ($tenant->monthly_fee_per_store ?? 11000);
                if (!empty($stores)) {
                    foreach ($stores as $store) {
                        $items[] = [
                            'description' => "{$store->name} 月額利用料（{$period->format('Y/m')}）",
                            'quantity' => 1,
                            'unit_price' => $perStore,
                        ];
                    }
                } else {
                    // 店舗 0 件 → 基本料金 1 店舗分だけ計上
                    $items[] = [
                        'description' => "QRレビュー月額利用料（{$period->format('Y/m')}）店舗未設定",
                        'quantity' => 1,
                        'unit_price' => $perStore,
                    ];
                }
            }

            $this->createInvoiceForTenant($tenant, [
                'billing_period_start' => $period->copy()->startOfMonth()->toDateString(),
                'billing_period_end' => $period->copy()->endOfMonth()->toDateString(),
                'issue_date' => $issue->toDateString(),
                'due_date' => $due->toDateString(),
                'tax_rate' => $validated['tax_rate'],
                'notes' => null,
                'items' => $items,
                'status' => 'sent', // 一括発行は最初から「送付済み」扱い
            ]);
            $created++;
        }

        return redirect('/super-admin/invoices')
            ->with('success', "請求書を {$created} 件発行しました。（スキップ: {$skipped} 件 - 既発行）");
    }

    // ========================================================
    // ヘルパー
    // ========================================================

    /**
     * 共通の請求書作成処理
     */
    private function createInvoiceForTenant(Tenant $tenant, array $params): Invoice
    {
        $taxRate = (float) ($params['tax_rate'] ?? 10.0);
        $subtotal = 0;
        foreach ($params['items'] as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
        $taxAmount = (int) round($subtotal * ($taxRate / 100));
        $total = $subtotal + $taxAmount;

        $issueDate = Carbon::parse($params['issue_date']);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'invoice_number' => Invoice::nextInvoiceNumber($issueDate),
            'billing_period_start' => $params['billing_period_start'],
            'billing_period_end' => $params['billing_period_end'],
            'issue_date' => $params['issue_date'],
            'due_date' => $params['due_date'],
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
            'status' => $params['status'] ?? 'draft',
            'notes' => $params['notes'] ?? null,
            'billing_company_name_snapshot' => $tenant->billing_company_name ?: $tenant->company_name,
            'billing_address_snapshot' => $tenant->billing_address,
            'billing_postal_code_snapshot' => $tenant->billing_postal_code,
        ]);

        foreach ($params['items'] as $idx => $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => $item['quantity'] * $item['unit_price'],
                'sort_order' => $idx + 1,
            ]);
        }

        return $invoice;
    }

    /**
     * テナント DB から店舗一覧を取得
     */
    private function fetchStoresForTenant(Tenant $tenant): array
    {
        try {
            Config::set('database.connections.tenant_check.driver', 'mysql');
            Config::set('database.connections.tenant_check.host', config('database.connections.mysql.host'));
            Config::set('database.connections.tenant_check.port', config('database.connections.mysql.port'));
            Config::set('database.connections.tenant_check.database', $tenant->db_name);
            Config::set('database.connections.tenant_check.username', $tenant->db_username ?: config('database.connections.mysql.username'));
            Config::set('database.connections.tenant_check.password', $tenant->db_password ?: config('database.connections.mysql.password'));

            $stores = DB::connection('tenant_check')
                ->table('stores')
                ->select('id', 'name')
                ->where('is_active', true)
                ->orderBy('id')
                ->get();
            DB::purge('tenant_check');
            return $stores->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
