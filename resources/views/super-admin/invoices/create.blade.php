@extends('layouts.super-admin')

@section('title', '請求書 個別発行')

@section('content')
<div class="page-header">
    <h1>📄 請求書 個別発行</h1>
    <a href="/super-admin/invoices" class="btn btn-secondary">← 一覧に戻る</a>
</div>

<form method="POST" action="/super-admin/invoices" id="invoiceForm">
    @csrf

    <div class="card" style="margin-bottom:20px;">
        <div class="card-body">
            <h3 style="margin-top:0;">🏢 対象テナント・期間</h3>
            <div class="two-col">
                <div class="form-group">
                    <label>対象テナント <span style="color:#ef4444">*</span></label>
                    <select name="tenant_id" required onchange="window.location='/super-admin/invoices/create?tenant_id='+this.value">
                        <option value="">選択してください</option>
                        @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ $selectedTenant && $selectedTenant->id == $t->id ? 'selected' : '' }}>{{ $t->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>消費税率（％） <span style="color:#ef4444">*</span></label>
                    <input type="number" name="tax_rate" value="10" min="0" max="100" step="0.01" required>
                </div>
            </div>
            <div class="two-col">
                <div class="form-group">
                    <label>請求対象期間（開始） <span style="color:#ef4444">*</span></label>
                    <input type="date" name="billing_period_start" value="{{ now()->startOfMonth()->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label>請求対象期間（終了） <span style="color:#ef4444">*</span></label>
                    <input type="date" name="billing_period_end" value="{{ now()->endOfMonth()->toDateString() }}" required>
                </div>
            </div>
            <div class="two-col">
                <div class="form-group">
                    <label>発行日 <span style="color:#ef4444">*</span></label>
                    <input type="date" name="issue_date" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label>支払期限 <span style="color:#ef4444">*</span></label>
                    <input type="date" name="due_date" value="{{ now()->addDays(30)->toDateString() }}" required>
                </div>
            </div>
        </div>
    </div>

    @if($selectedTenant)
    <div class="card">
        <div class="card-body">
            <h3 style="margin-top:0;">📋 請求明細</h3>
            <p style="font-size:0.85rem;color:#6b7280;margin-bottom:14px;">
                既定では店舗ごとに 1 行ずつ自動入力されます。割引や追加サービス料を加えるには「+ 行を追加」ボタンを使ってください。
            </p>

            <style>
                .invoice-items-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
                .invoice-items-table th, .invoice-items-table td {
                    padding: 8px 6px;
                    border: 1px solid #e5e7eb;
                    vertical-align: middle;
                    word-break: break-all;
                }
                .invoice-items-table input[type="text"],
                .invoice-items-table input[type="number"] {
                    width: 100%;
                    padding: 6px 8px;
                    border: 1px solid #d1d5db;
                    border-radius: 4px;
                    font-size: 0.85rem;
                    box-sizing: border-box;
                }
                .invoice-items-table .col-desc { width: auto; }
                .invoice-items-table .col-qty { width: 64px; }
                .invoice-items-table .col-price { width: 100px; }
                .invoice-items-table .col-tax { width: 90px; text-align: right; color:#6b7280; font-size:0.82rem; }
                .invoice-items-table .col-amount { width: 110px; text-align: right; }
                .invoice-items-table .col-del { width: 40px; text-align: center; }
                .invoice-items-table input.qty-input,
                .invoice-items-table input.price-input { text-align: right; }
                .invoice-summary {
                    margin-top: 20px;
                    padding: 14px 18px;
                    background: #f9fafb;
                    border-radius: 8px;
                    overflow-x: auto;
                }
                .invoice-summary table { border: none; min-width: 280px; margin-left: auto; }
                .invoice-summary td { border: none; padding: 4px 14px; white-space: nowrap; }
            </style>

            <div style="overflow-x:auto;">
                <table id="itemsTable" class="invoice-items-table">
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th class="col-desc">項目</th>
                            <th class="col-qty">数量</th>
                            <th class="col-price">単価（円）</th>
                            <th class="col-tax">消費税</th>
                            <th class="col-amount">金額（税抜）</th>
                            <th class="col-del"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        @php
                            $perStore = (int) ($selectedTenant->monthly_fee_per_store ?? 11000);
                            $period = now()->format('Y/m');
                        @endphp
                        @foreach($stores as $store)
                        <tr>
                            <td class="col-desc"><input type="text" name="items[{{ $loop->index }}][description]" value="{{ $store->name }} 月額利用料（{{ $period }}）" required></td>
                            <td class="col-qty"><input type="number" class="qty-input" name="items[{{ $loop->index }}][quantity]" value="1" min="1" required onchange="recalcRow(this)"></td>
                            <td class="col-price"><input type="number" class="price-input" name="items[{{ $loop->index }}][unit_price]" value="{{ $perStore }}" min="0" required onchange="recalcRow(this)"></td>
                            <td class="col-tax tax">¥{{ number_format((int) round($perStore * 10 / 100)) }}</td>
                            <td class="col-amount amount" style="font-weight:600;">¥{{ number_format($perStore) }}</td>
                            <td class="col-del"><button type="button" onclick="removeRow(this)" style="background:none;border:none;color:#ef4444;font-size:1rem;cursor:pointer;padding:4px 8px;">×</button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button type="button" onclick="addRow()" class="btn btn-secondary btn-sm" style="margin-top:10px;">+ 行を追加</button>

            <div class="invoice-summary">
                <table>
                    <tr><td style="text-align:right;">小計（税抜）</td><td style="text-align:right;font-weight:600;" id="subtotalDisplay">¥0</td></tr>
                    <tr><td style="text-align:right;">消費税</td><td style="text-align:right;font-weight:600;" id="taxDisplay">¥0</td></tr>
                    <tr><td style="text-align:right;font-size:1.1rem;">合計（税込）</td><td style="text-align:right;font-weight:700;font-size:1.1rem;color:#1e1b4b;" id="totalDisplay">¥0</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <div class="card-body">
            <div class="form-group">
                <label>備考</label>
                <textarea name="notes" rows="3" placeholder="お客様への伝達事項があれば記入"></textarea>
            </div>
        </div>
    </div>

    <div style="margin-top:20px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <button type="submit" name="status" value="draft" class="btn btn-secondary" style="padding:14px 28px;font-size:0.95rem;">
            💾 下書き保存
        </button>
        <button type="submit" name="status" value="sent" class="btn btn-primary" style="padding:14px 36px;font-size:1rem;background:linear-gradient(135deg,#10b981,#059669);">
            ✅ 発行（送付済み）
        </button>
    </div>
    <p style="text-align:center;font-size:0.78rem;color:#6b7280;margin-top:10px;">
        💡 「下書き保存」: テナント側には表示されません（運営側のみ確認可）<br>
        💡 「発行」: テナント側の請求書一覧に表示され、ダウンロード可能になります
    </p>
    @endif
</form>

<script>
let itemIdx = {{ count($stores ?? []) }};

function addRow() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="col-desc"><input type="text" name="items[${itemIdx}][description]" required></td>
        <td class="col-qty"><input type="number" class="qty-input" name="items[${itemIdx}][quantity]" value="1" min="1" required onchange="recalcRow(this)"></td>
        <td class="col-price"><input type="number" class="price-input" name="items[${itemIdx}][unit_price]" value="0" min="0" required onchange="recalcRow(this)"></td>
        <td class="col-tax tax">¥0</td>
        <td class="col-amount amount" style="font-weight:600;">¥0</td>
        <td class="col-del"><button type="button" onclick="removeRow(this)" style="background:none;border:none;color:#ef4444;font-size:1rem;cursor:pointer;padding:4px 8px;">×</button></td>
    `;
    tbody.appendChild(tr);
    itemIdx++;
    recalc();
}

function removeRow(btn) {
    btn.closest('tr').remove();
    recalc();
}

function recalcRow(input) {
    const tr = input.closest('tr');
    const qty = parseInt(tr.querySelector('input[name*="quantity"]').value) || 0;
    const price = parseInt(tr.querySelector('input[name*="unit_price"]').value) || 0;
    const taxRate = parseFloat(document.querySelector('input[name="tax_rate"]').value) || 0;
    const lineSubtotal = qty * price;
    const lineTax = Math.round(lineSubtotal * taxRate / 100);
    tr.querySelector('.amount').textContent = '¥' + lineSubtotal.toLocaleString();
    tr.querySelector('.tax').textContent = '¥' + lineTax.toLocaleString();
    recalc();
}

function recalc() {
    const taxRate = parseFloat(document.querySelector('input[name="tax_rate"]').value) || 0;
    let subtotal = 0;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const qty = parseInt(tr.querySelector('input[name*="quantity"]')?.value) || 0;
        const price = parseInt(tr.querySelector('input[name*="unit_price"]')?.value) || 0;
        const lineSubtotal = qty * price;
        const lineTax = Math.round(lineSubtotal * taxRate / 100);
        subtotal += lineSubtotal;
        // 行ごとの消費税表示も更新（税率変更時に追従）
        const taxCell = tr.querySelector('.tax');
        if (taxCell) taxCell.textContent = '¥' + lineTax.toLocaleString();
    });
    const tax = Math.round(subtotal * taxRate / 100);
    const total = subtotal + tax;
    document.getElementById('subtotalDisplay').textContent = '¥' + subtotal.toLocaleString();
    document.getElementById('taxDisplay').textContent = '¥' + tax.toLocaleString();
    document.getElementById('totalDisplay').textContent = '¥' + total.toLocaleString();
}

document.addEventListener('DOMContentLoaded', recalc);
document.querySelector('input[name="tax_rate"]')?.addEventListener('input', recalc);
</script>
@endsection
