<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }} - 請求書</title>
    <style>
        body {
            font-family: "Hiragino Kaku Gothic ProN", "Hiragino Sans", "Yu Gothic", Meiryo, sans-serif;
            margin: 0;
            padding: 30px;
            color: #1f2937;
            background: #f3f4f6;
        }
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
        }
        .toolbar {
            max-width: 820px;
            margin: 0 auto 20px;
            padding: 12px 18px;
            background: white;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .container {
            max-width: 820px;
            margin: 0 auto;
        }
        .btn {
            padding: 8px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
        }
        .btn-primary { background: #6366f1; color: white; }
        .btn-secondary { background: #f3f4f6; color: #1f2937; border: 1px solid #d1d5db; }
    </style>
</head>
<body>
<div class="toolbar no-print">
    <div style="font-size:0.85rem;color:#6b7280;">印刷プレビュー: {{ $invoice->invoice_number }}</div>
    <div style="display:flex;gap:8px;">
        <button onclick="window.print()" class="btn btn-primary">🖨️ 印刷 / PDF 保存</button>
        <a href="/super-admin/invoices/{{ $invoice->id }}" class="btn btn-secondary">← 戻る</a>
    </div>
</div>

<div class="container">
    @include('super-admin.invoices._invoice-document', ['invoice' => $invoice])
</div>
</body>
</html>
