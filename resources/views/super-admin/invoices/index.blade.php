@extends('layouts.super-admin')

@section('title', '請求書一覧')

@section('content')
<style>
    .invoice-list-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .invoice-list-table { min-width: 1000px; white-space: nowrap; }
    .invoice-list-table th, .invoice-list-table td {
        padding: 8px 10px !important;
        font-size: 0.83rem;
        vertical-align: middle;
    }
    .invoice-list-table .actions-cell .btn {
        padding: 4px 8px;
        font-size: 0.72rem;
        margin-right: 2px;
    }
    /* 入金済み行を視覚的にハイライト */
    .invoice-list-table tr.row-paid { background: #ecfdf5; }
    .invoice-list-table tr.row-paid td { color: #065f46; }
    /* 期限超過行を強調 */
    .invoice-list-table tr.row-overdue { background: #fef2f2; }
    /* 下書き行を薄く */
    .invoice-list-table tr.row-draft td { color: #9ca3af; }
    .quick-paid-btn {
        background: #10b981;
        color: white;
        border: none;
        padding: 4px 10px;
        font-size: 0.72rem;
        border-radius: 4px;
        cursor: pointer;
    }
    .quick-paid-btn:hover { background: #059669; }

    /* 一括入金バー */
    .bulk-action-bar {
        display: none;
        background: linear-gradient(135deg,#10b981,#059669);
        color: white;
        padding: 12px 18px;
        border-radius: 10px;
        margin-bottom: 14px;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .bulk-action-bar.active { display: flex; }
    .bulk-action-bar input[type="date"] {
        padding: 6px 10px;
        border: none;
        border-radius: 6px;
        font-size: 0.85rem;
        color: #1e1b4b;
    }
    .bulk-action-bar button {
        background: white;
        color: #059669;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.85rem;
    }
    .bulk-action-bar button:hover { background: #f0fdf4; }
    .bulk-action-bar .selected-count {
        font-size: 0.95rem;
        font-weight: 600;
    }
    .bulk-action-bar .clear-link {
        color: rgba(255,255,255,0.85);
        cursor: pointer;
        text-decoration: underline;
        font-size: 0.78rem;
    }
    .invoice-list-table .col-check { width: 36px; text-align: center; }
</style>
<div class="page-header">
    <h1>📄 請求書一覧</h1>
    <div class="btn-group">
        <a href="/super-admin/invoices/bulk" class="btn btn-primary" style="background:linear-gradient(135deg,#10b981,#059669);">📅 月次一括発行</a>
        <a href="/super-admin/invoices/create" class="btn btn-secondary">＋ 個別発行</a>
    </div>
</div>

{{-- 統計カード --}}
<style>
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: white;
        padding: 12px 20px 14px;
        border-radius: 12px;
        border-left: 4px solid var(--accent, #6366f1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 6px;
        min-height: 80px;
    }
    .stat-card .stat-label {
        font-size: 0.78rem;
        color: #6b7280;
        line-height: 1.3;
    }
    .stat-card .stat-values {
        display: flex;
        align-items: baseline;
        gap: 10px;
        flex-wrap: wrap;
    }
    .stat-card .stat-count {
        font-size: 1.1rem;
        font-weight: 700;
        line-height: 1.2;
        color: #1e1b4b;
        white-space: nowrap;
    }
    .stat-card .stat-amount {
        font-size: 1rem;
        font-weight: 600;
        color: #4b5563;
        line-height: 1.2;
        white-space: nowrap;
    }
    .stat-card.accent-green  { background: #ecfdf5; border-left-color: #10b981; }
    .stat-card.accent-green  .stat-label  { color: #065f46; }
    .stat-card.accent-green  .stat-count  { color: #065f46; }
    .stat-card.accent-green  .stat-amount { color: #047857; }
    .stat-card.accent-amber  { border-left-color: #f59e0b; }
    .stat-card.accent-amber  .stat-count  { color: #92400e; }
    .stat-card.accent-amber  .stat-amount { color: #b45309; }
    .stat-card.accent-teal   { border-left-color: #14b8a6; }
    .stat-card.accent-teal   .stat-count  { color: #0f766e; }
    .stat-card.accent-teal   .stat-amount { color: #0d9488; }
    .stat-card.accent-indigo { border-left-color: #6366f1; }
    .stat-card.accent-indigo .stat-count  { color: #3730a3; }
    .stat-card.accent-indigo .stat-amount { color: #4338ca; }
</style>
<div class="stat-grid">
    <div class="stat-card accent-indigo">
        <div class="stat-label">総請求書数</div>
        <div class="stat-values">
            <div class="stat-count">{{ number_format($stats['total_count']) }} 件</div>
            <div class="stat-amount">¥{{ number_format($stats['total_amount']) }}</div>
        </div>
    </div>
    <div class="stat-card accent-amber">
        <div class="stat-label">未入金</div>
        <div class="stat-values">
            <div class="stat-count">{{ number_format($stats['unpaid_count']) }} 件</div>
            <div class="stat-amount">¥{{ number_format($stats['unpaid_amount']) }}</div>
        </div>
    </div>
    <div class="stat-card accent-green">
        <div class="stat-label">✅ 入金済み（累計）</div>
        <div class="stat-values">
            <div class="stat-count">{{ number_format($stats['paid_count']) }} 件</div>
            <div class="stat-amount">¥{{ number_format($stats['paid_amount']) }}</div>
        </div>
    </div>
    <div class="stat-card accent-teal">
        <div class="stat-label">今月入金</div>
        <div class="stat-values">
            <div class="stat-count">{{ number_format($stats['this_month_paid_count']) }} 件</div>
            <div class="stat-amount">¥{{ number_format($stats['this_month_paid_amount']) }}</div>
        </div>
    </div>
</div>

{{-- フィルタ --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <form method="GET" action="/super-admin/invoices" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin:0;">
                <label style="font-size:0.8rem;">テナント</label>
                <select name="tenant" style="min-width:200px;">
                    <option value="">すべて</option>
                    @foreach($tenants as $t)
                    <option value="{{ $t->id }}" {{ request('tenant') == $t->id ? 'selected' : '' }}>{{ $t->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:0.8rem;">ステータス</label>
                <select name="status">
                    <option value="">すべて</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>下書き</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>送付済み</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>入金済み</option>
                    <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>期限超過</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>取消</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:0.8rem;">対象月</label>
                <input type="month" name="month" value="{{ request('month') }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">絞り込み</button>
            <a href="/super-admin/invoices" class="btn btn-secondary btn-sm">クリア</a>
        </form>
    </div>
</div>

{{-- 一括入金フォーム（テーブル外に配置・チェックボックスとは form 属性で紐付け） --}}
<form method="POST" action="/super-admin/invoices/bulk-paid" id="bulkPaidForm"
      onsubmit="return confirmBulkPaid()">
    @csrf
    <div class="bulk-action-bar" id="bulkActionBar">
        <span class="selected-count">✅ <span id="selectedCount">0</span> 件選択中</span>
        <label style="font-size:0.85rem;">入金日:
            <input type="date" name="paid_date" required value="{{ now()->toDateString() }}">
        </label>
        <button type="submit">一括で入金済みにする</button>
        <span class="clear-link" onclick="clearSelection()">選択をクリア</span>
    </div>
</form>

<div class="card">
    <div class="card-body invoice-list-wrap" style="padding:0;">
        <table class="invoice-list-table">
            <thead>
                <tr>
                    <th class="col-check">
                        <input type="checkbox" id="selectAll" title="未入金のみ全選択" onchange="toggleSelectAll(this)" form="bulkPaidForm">
                    </th>
                    <th>請求書番号</th>
                    <th>会社名</th>
                    <th>対象期間</th>
                    <th>発行日</th>
                    <th>支払期限</th>
                    <th style="text-align:right;">金額</th>
                    <th>状態</th>
                    <th>入金日</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                @php
                    // paid_date が入っている請求書は status が未更新でも入金扱いで表示する
                    $isEffectivelyPaid = $inv->status === 'paid' || $inv->paid_date;
                    $rowClass = match(true) {
                        $isEffectivelyPaid          => 'row-paid',
                        $inv->status === 'overdue'  => 'row-overdue',
                        $inv->status === 'draft'    => 'row-draft',
                        default                     => '',
                    };
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="col-check">
                        @if(in_array($inv->status, ['sent','overdue'], true) && !$inv->paid_date)
                            <input type="checkbox" name="invoice_ids[]" value="{{ $inv->id }}" class="row-check" onchange="updateBulkBar()" form="bulkPaidForm">
                        @endif
                    </td>
                    <td><code style="font-size:0.78rem;">{{ $inv->invoice_number }}</code></td>
                    <td>{{ $inv->tenant->company_name ?? '(削除済み)' }}</td>
                    <td style="font-size:0.78rem;">
                        {{ $inv->billing_period_start->format('Y/n/j') }} 〜<br>
                        {{ $inv->billing_period_end->format('Y/n/j') }}
                    </td>
                    <td>{{ $inv->issue_date->format('Y/n/j') }}</td>
                    <td>{{ $inv->due_date->format('Y/n/j') }}</td>
                    <td style="text-align:right;font-weight:600;">¥{{ number_format($inv->total_amount) }}</td>
                    <td>
                        <span style="background:{{ $inv->statusColor() }};color:white;padding:2px 8px;border-radius:10px;font-size:0.72rem;">
                            @if($inv->status === 'paid')✅ @endif{{ $inv->statusLabel() }}
                        </span>
                    </td>
                    <td style="font-size:0.78rem;">
                        @if($inv->paid_date)
                            <span style="color:#065f46;font-weight:600;">{{ $inv->paid_date->format('Y/n/j') }}</span>
                        @else
                            <span style="color:#9ca3af;">—</span>
                        @endif
                    </td>
                    <td class="actions-cell">
                        <a href="/super-admin/invoices/{{ $inv->id }}" class="btn btn-secondary">詳細</a>
                        <a href="/super-admin/invoices/{{ $inv->id }}/print" target="_blank" class="btn" style="background:#3b82f6;color:white;padding:4px 8px;font-size:0.72rem;">🖨️</a>
                        @if(in_array($inv->status, ['sent','overdue'], true) && !$inv->paid_date)
                        <form method="POST" action="/super-admin/invoices/{{ $inv->id }}/status" style="display:inline;"
                              onsubmit="return confirm('「{{ $inv->invoice_number }}」を入金済みにしますか？\n入金日は本日になります。')">
                            @csrf @method('PUT')
                            <input type="hidden" name="status" value="paid">
                            <input type="hidden" name="paid_date" value="{{ now()->toDateString() }}">
                            <button type="submit" class="quick-paid-btn">✅ 入金</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:30px;color:#6b7280;white-space:normal;">請求書がありません。「個別発行」または「月次一括発行」から作成してください。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($invoices, 'links'))
<div style="margin-top:20px;">{{ $invoices->links() }}</div>
@endif

<script>
function updateBulkBar() {
    const checked = document.querySelectorAll('.row-check:checked');
    const bar = document.getElementById('bulkActionBar');
    const cnt = document.getElementById('selectedCount');
    cnt.textContent = checked.length;
    bar.classList.toggle('active', checked.length > 0);
    // 全選択チェックの状態を反映
    const allChecks = document.querySelectorAll('.row-check');
    const selectAll = document.getElementById('selectAll');
    if (allChecks.length === 0) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    } else if (checked.length === allChecks.length) {
        selectAll.checked = true;
        selectAll.indeterminate = false;
    } else if (checked.length === 0) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    } else {
        selectAll.indeterminate = true;
    }
}
function toggleSelectAll(master) {
    document.querySelectorAll('.row-check').forEach(c => { c.checked = master.checked; });
    updateBulkBar();
}
function clearSelection() {
    document.querySelectorAll('.row-check').forEach(c => { c.checked = false; });
    updateBulkBar();
}
function confirmBulkPaid() {
    const cnt = document.querySelectorAll('.row-check:checked').length;
    if (cnt === 0) { alert('請求書を1件以上選択してください。'); return false; }
    const date = document.querySelector('#bulkActionBar input[name="paid_date"]').value;
    if (!date) { alert('入金日を指定してください。'); return false; }
    return confirm(`${cnt} 件の請求書を入金済み（入金日: ${date}）にします。よろしいですか？`);
}
document.addEventListener('DOMContentLoaded', updateBulkBar);
</script>
@endsection
