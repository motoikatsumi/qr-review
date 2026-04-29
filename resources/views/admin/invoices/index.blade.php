@extends('layouts.admin')

@section('title', '請求書一覧')

@push('styles')
<style>
    .invoice-list-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .invoice-list-table th, .invoice-list-table td {
        padding: 10px 12px;
        font-size: 0.88rem;
        vertical-align: middle;
        white-space: nowrap;
    }
    .invoice-stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }
    .invoice-stat {
        background: white;
        padding: 18px 22px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border-left: 4px solid #6366f1;
    }
    .invoice-stat-label { font-size: 0.78rem; color: #6b7280; }
    .invoice-stat-value { font-size: 1.4rem; font-weight: 700; margin-top: 4px; color: #1e1b4b; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>📄 請求書一覧</h1>
</div>

@if(isset($error))
<div class="alert alert-error">{{ $error }}</div>
@endif

@if($tenant)
<p style="color:#6b7280;font-size:0.9rem;margin-bottom:20px;">
    {{ $tenant->company_name }} 様への請求書一覧です。請求書の内容は支払期限内に確認の上、お振込みください。
</p>

<div class="invoice-stat-grid">
    <div class="invoice-stat">
        <div class="invoice-stat-label">発行された請求書</div>
        <div class="invoice-stat-value">{{ $stats['total'] }} 件</div>
    </div>
    <div class="invoice-stat" style="border-left-color:#f59e0b;">
        <div class="invoice-stat-label">未入金</div>
        <div class="invoice-stat-value" style="color:#92400e;">
            {{ $stats['unpaid'] }} 件 / ¥{{ number_format($stats['unpaid_amount']) }}
        </div>
    </div>
</div>

{{-- フィルタ --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <form method="GET" action="/admin/invoices" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin:0;">
                <label style="font-size:0.8rem;">ステータス</label>
                <select name="status">
                    <option value="">すべて</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>送付済み（要支払い）</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>入金済み</option>
                    <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>期限超過</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">絞り込み</button>
            <a href="/admin/invoices" class="btn btn-secondary btn-sm">クリア</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body invoice-list-wrap" style="padding:0;">
        <table class="invoice-list-table">
            <thead>
                <tr>
                    <th>請求書番号</th>
                    <th>対象期間</th>
                    <th>発行日</th>
                    <th>支払期限</th>
                    <th style="text-align:right;">金額</th>
                    <th>状態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                <tr>
                    <td><code style="font-size:0.82rem;">{{ $inv->invoice_number }}</code></td>
                    <td style="font-size:0.82rem;">
                        {{ $inv->billing_period_start->format('Y/n/j') }} 〜 {{ $inv->billing_period_end->format('Y/n/j') }}
                    </td>
                    <td>{{ $inv->issue_date->format('Y/n/j') }}</td>
                    <td>
                        {{ $inv->due_date->format('Y/n/j') }}
                        @if(in_array($inv->status, ['sent']) && $inv->due_date->isPast())
                            <span style="color:#dc2626;font-size:0.72rem;font-weight:600;">⚠️ 期限超過</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-weight:600;">¥{{ number_format($inv->total_amount) }}</td>
                    <td>
                        <span style="background:{{ $inv->statusColor() }};color:white;padding:3px 10px;border-radius:12px;font-size:0.78rem;">{{ $inv->statusLabel() }}</span>
                    </td>
                    <td>
                        <a href="/admin/invoices/{{ $inv->id }}" class="btn btn-secondary btn-sm">詳細</a>
                        <a href="/admin/invoices/{{ $inv->id }}/print" target="_blank" class="btn btn-info btn-sm">🖨️ 印刷</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:#6b7280;white-space:normal;">
                        請求書はまだ発行されていません。
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($invoices, 'links'))
<div style="margin-top:20px;">{{ $invoices->links() }}</div>
@endif
@endif
@endsection
