@extends('layouts.super-admin')

@section('title', $invoice->invoice_number)

@section('content')
<div class="page-header">
    <h1>📄 請求書詳細</h1>
    <div class="btn-group">
        <a href="/super-admin/invoices" class="btn btn-secondary">← 一覧</a>
        <a href="/super-admin/invoices/{{ $invoice->id }}/print" target="_blank" class="btn btn-info">🖨️ 印刷プレビュー</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
    <div>
        @include('super-admin.invoices._invoice-document', ['invoice' => $invoice])
    </div>

    <div>
        <div class="card" style="margin-bottom:14px;">
            <div class="card-body">
                <h3 style="margin-top:0;">ステータス管理</h3>
                <div style="margin-bottom:14px;">
                    <span style="background:{{ $invoice->statusColor() }};color:white;padding:6px 14px;border-radius:14px;font-size:0.9rem;">{{ $invoice->statusLabel() }}</span>
                </div>

                @if($invoice->status === 'draft')
                <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:10px 12px;border-radius:6px;font-size:0.82rem;color:#92400e;margin-bottom:14px;line-height:1.6;">
                    ⚠️ 下書き状態です。<br>テナント側には<strong>まだ表示されていません</strong>。発行するとテナント画面でダウンロード可能になります。
                </div>
                <form method="POST" action="/super-admin/invoices/{{ $invoice->id }}/status" style="margin-bottom:14px;">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="sent">
                    <button type="submit" class="btn btn-primary btn-sm" style="background:linear-gradient(135deg,#10b981,#059669);width:100%;padding:10px;">
                        ✅ 発行する（送付済みに変更）
                    </button>
                </form>
                @endif

                <form method="POST" action="/super-admin/invoices/{{ $invoice->id }}/status" id="statusForm">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label>ステータス変更</label>
                        <select name="status" id="statusSelect" onchange="onStatusChange()">
                            <option value="draft" {{ $invoice->status === 'draft' ? 'selected' : '' }}>下書き</option>
                            <option value="sent" {{ $invoice->status === 'sent' ? 'selected' : '' }}>送付済み</option>
                            <option value="paid" {{ $invoice->status === 'paid' ? 'selected' : '' }}>入金済み</option>
                            <option value="overdue" {{ $invoice->status === 'overdue' ? 'selected' : '' }}>期限超過</option>
                            <option value="cancelled" {{ $invoice->status === 'cancelled' ? 'selected' : '' }}>取消</option>
                        </select>
                    </div>
                    <div class="form-group" id="paidDateGroup">
                        <label>入金日 <span id="paidDateRequired" style="color:#ef4444;display:none;">*</span></label>
                        <input type="date" name="paid_date" id="paidDateInput" value="{{ $invoice->paid_date?->format('Y-m-d') }}">
                        <p class="form-hint" id="paidDateHint" style="font-size:0.78rem;color:#6b7280;margin-top:4px;">「入金済み」を選択した場合は必須です</p>
                    </div>
                    <button type="submit" class="btn btn-secondary btn-sm">更新</button>
                    @error('paid_date') <p style="color:#ef4444;font-size:0.8rem;margin-top:6px;">{{ $message }}</p> @enderror
                </form>
                <script>
                function onStatusChange() {
                    const sel = document.getElementById('statusSelect');
                    const inp = document.getElementById('paidDateInput');
                    const req = document.getElementById('paidDateRequired');
                    if (sel.value === 'paid') {
                        inp.required = true;
                        req.style.display = 'inline';
                        if (!inp.value) inp.value = new Date().toISOString().slice(0,10);
                    } else {
                        inp.required = false;
                        req.style.display = 'none';
                    }
                }
                document.addEventListener('DOMContentLoaded', onStatusChange);
                </script>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h3 style="margin-top:0;color:#dc2626;">⚠️ 削除</h3>
                <form method="POST" action="/super-admin/invoices/{{ $invoice->id }}"
                      onsubmit="return confirm('この請求書を削除しますか？元に戻せません。')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">削除</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
