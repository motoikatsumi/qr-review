@extends('layouts.admin')

@section('title', 'QRコード - ' . $store->name)

@push('styles')
<style>
    .qr-preview {
        text-align: center;
        padding: 40px 20px;
    }
    .qr-image {
        background: white;
        inline-size: fit-content;
        margin: 0 auto;
        padding: 24px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: 2px solid #e5e7eb;
    }
    .qr-image img {
        display: block;
        width: 280px;
        height: 280px;
    }
    .qr-url {
        margin-top: 16px;
        font-size: 0.85rem;
        color: #667eea;
        word-break: break-all;
    }
    .qr-store-name {
        font-size: 1.2rem;
        font-weight: 700;
        color: #333;
        margin-top: 20px;
    }
    .qr-actions {
        margin-top: 24px;
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .print-section {
        margin-top: 32px;
        padding: 24px;
        background: #f8f9ff;
        border-radius: 12px;
        border: 1px dashed #667eea;
    }
    .print-tip {
        font-size: 0.85rem;
        color: #555;
        line-height: 1.7;
    }
    @media print {
        .navbar, .page-header, .qr-actions, .print-section, .alert { display: none !important; }
        body { background: white !important; }
        .container { max-width: 100% !important; }
        .card { box-shadow: none !important; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>📱 QRコード：{{ $store->name }}</h1>
    <a href="/admin/stores" class="btn btn-secondary">← 店舗一覧に戻る</a>
</div>

<div class="card">
    <div class="qr-preview">
        <div class="qr-image">
            {!! $qrCode !!}
        </div>
        <p class="qr-store-name">{{ $store->name }}</p>
        <p class="qr-url">{{ $reviewUrl }}</p>

        <div class="qr-actions">
            <a href="/admin/stores/{{ $store->id }}/qrcode/download" class="btn btn-primary">📥 PNGをダウンロード</a>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ 印刷する</button>
        </div>
    </div>

    <div class="print-section">
        <h3 style="font-size:0.95rem;margin-bottom:8px;">💡 テーブル設置のヒント</h3>
        <div class="print-tip">
            <ul style="padding-left:20px;">
                <li>ダウンロードしたQRコード画像をPOPカードに貼り付けて各テーブルに設置してください</li>
                <li>「ご来店の感想をお聞かせください」などの案内文を添えると回答率がアップします</li>
                <li>推奨印刷サイズ：4cm × 4cm 以上</li>
            </ul>
        </div>
    </div>
</div>
@endsection
