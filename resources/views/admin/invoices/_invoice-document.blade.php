{{-- 請求書ドキュメント本体（show + print 両方で使う） --}}
<div style="background:white;padding:40px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
    <h1 style="text-align:center;font-size:1.8rem;letter-spacing:0.4em;border-bottom:3px solid #1e1b4b;padding-bottom:14px;margin:0 0 30px;color:#1e1b4b;">請求書</h1>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-bottom:30px;">
        {{-- 宛名（左） --}}
        <div>
            @if($invoice->billing_postal_code_snapshot)
                <div style="font-size:0.88rem;">〒{{ $invoice->billing_postal_code_snapshot }}</div>
            @endif
            @if($invoice->billing_address_snapshot)
                <div style="font-size:0.88rem;">{{ $invoice->billing_address_snapshot }}</div>
            @endif
            <div style="font-size:1.4rem;font-weight:700;margin-top:8px;border-bottom:1px solid #1e1b4b;padding-bottom:4px;">
                {{ $invoice->billing_company_name_snapshot ?: $invoice->tenant->company_name }} 御中
            </div>
        </div>

        {{-- 発行元（右） --}}
        <div style="text-align:right;font-size:0.85rem;">
            <div style="font-weight:600;">アシストホールディングス株式会社</div>
            <div>〒892-0847 鹿児島県鹿児島市西千石町14番2号ASSISTビル4F</div>
            <div>TEL: 099-225-0400</div>
            <div>info@assist-grp.jp</div>
            <div style="margin-top:4px;font-size:0.82rem;">登録番号: T3340001027294</div>
            <div style="margin-top:14px;font-size:0.85rem;color:#6b7280;">
                請求書番号: <strong>{{ $invoice->invoice_number }}</strong><br>
                発行日: {{ $invoice->issue_date->format('Y年n月j日') }}<br>
                支払期限: <strong>{{ $invoice->due_date->format('Y年n月j日') }}</strong>
            </div>
        </div>
    </div>

    {{-- 件名 --}}
    <div style="background:#f0f4ff;padding:14px 20px;border-radius:6px;margin-bottom:20px;">
        <div style="font-size:0.85rem;color:#6b7280;">対象期間</div>
        <div style="font-size:1.1rem;font-weight:600;">{{ $invoice->billing_period_start->format('Y/n/j') }} 〜 {{ $invoice->billing_period_end->format('Y/n/j') }}</div>
    </div>

    {{-- 合計（強調） --}}
    <div style="background:#1e1b4b;color:white;padding:18px 24px;border-radius:6px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:1.05rem;">ご請求金額（税込）</span>
        <span style="font-size:1.8rem;font-weight:700;">¥ {{ number_format($invoice->total_amount) }}</span>
    </div>

    {{-- 明細 --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
        <thead>
            <tr style="background:#f9fafb;">
                <th style="text-align:left;padding:10px 14px;border:1px solid #e5e7eb;">項目</th>
                <th style="text-align:right;padding:10px 14px;border:1px solid #e5e7eb;width:80px;">数量</th>
                <th style="text-align:right;padding:10px 14px;border:1px solid #e5e7eb;width:130px;">単価</th>
                <th style="text-align:right;padding:10px 14px;border:1px solid #e5e7eb;width:140px;">金額</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td style="padding:10px 14px;border:1px solid #e5e7eb;">{{ $item->description }}</td>
                <td style="padding:10px 14px;border:1px solid #e5e7eb;text-align:right;">{{ $item->quantity }}</td>
                <td style="padding:10px 14px;border:1px solid #e5e7eb;text-align:right;">¥{{ number_format($item->unit_price) }}</td>
                <td style="padding:10px 14px;border:1px solid #e5e7eb;text-align:right;font-weight:600;">¥{{ number_format($item->amount) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;padding:10px 14px;border:1px solid #e5e7eb;background:#f9fafb;">小計（税抜）</td>
                <td style="text-align:right;padding:10px 14px;border:1px solid #e5e7eb;font-weight:600;">¥{{ number_format($invoice->subtotal) }}</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:right;padding:10px 14px;border:1px solid #e5e7eb;background:#f9fafb;">消費税（{{ $invoice->tax_rate }}％）</td>
                <td style="text-align:right;padding:10px 14px;border:1px solid #e5e7eb;font-weight:600;">¥{{ number_format($invoice->tax_amount) }}</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:right;padding:10px 14px;border:1px solid #e5e7eb;background:#1e1b4b;color:white;font-size:1.05rem;">合計（税込）</td>
                <td style="text-align:right;padding:12px 14px;border:1px solid #e5e7eb;font-size:1.15rem;font-weight:700;background:#1e1b4b;color:white;">¥{{ number_format($invoice->total_amount) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- 振込先 --}}
    <div style="background:#fef3c7;padding:14px 18px;border-radius:6px;margin-bottom:20px;">
        <div style="font-size:0.85rem;font-weight:600;margin-bottom:6px;color:#78350f;">📌 お振込先</div>
        <div style="font-size:0.85rem;color:#78350f;line-height:1.7;">
            宮崎銀行 鹿児島営業部<br>
            普通 0298415<br>
            アシストホ－ルデイングス．カ
        </div>
    </div>

    {{-- 備考 --}}
    @if($invoice->notes)
    <div style="background:#f9fafb;padding:14px 18px;border-radius:6px;font-size:0.85rem;color:#374151;">
        <strong>備考:</strong><br>
        {!! nl2br(e($invoice->notes)) !!}
    </div>
    @endif
</div>
