@extends('layouts.admin')

@section('title', $invoice->invoice_number)

@section('content')
<div class="page-header">
    <h1>📄 請求書詳細</h1>
    <div class="btn-group">
        <a href="/admin/invoices" class="btn btn-secondary">← 一覧</a>
        <a href="/admin/invoices/{{ $invoice->id }}/print" target="_blank" class="btn btn-primary">🖨️ 印刷 / PDF 保存</a>
    </div>
</div>

@if($invoice->status === 'sent' && $invoice->due_date->isPast())
<div class="alert" style="background:#fee2e2;border-left:4px solid #dc2626;color:#7f1d1d;padding:14px 18px;margin-bottom:20px;">
    ⚠️ <strong>支払期限が過ぎています。</strong> お早めにお振込みください。
</div>
@elseif($invoice->status === 'sent')
<div class="alert" style="background:#fef3c7;border-left:4px solid #f59e0b;color:#78350f;padding:14px 18px;margin-bottom:20px;">
    💴 <strong>支払期限: {{ $invoice->due_date->format('Y年n月j日') }}</strong>
    （あと {{ now()->diffInDays($invoice->due_date, false) }} 日）
</div>
@elseif($invoice->status === 'paid')
<div class="alert" style="background:#d1fae5;border-left:4px solid #10b981;color:#065f46;padding:14px 18px;margin-bottom:20px;">
    ✅ <strong>入金確認済み</strong>{{ $invoice->paid_date ? '（' . $invoice->paid_date->format('Y年n月j日') . '）' : '' }}
</div>
@endif

@include('admin.invoices._invoice-document', ['invoice' => $invoice])

<div style="margin-top:20px;text-align:center;">
    <a href="/admin/invoices/{{ $invoice->id }}/print" target="_blank" class="btn btn-primary" style="padding:12px 28px;">🖨️ 印刷 / PDF 保存</a>
</div>
@endsection
