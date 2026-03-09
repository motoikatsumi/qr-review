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

    /* ========== 印刷用カード ========== */
    .print-card-wrapper {
        margin-top: 32px;
        padding: 24px;
        background: #f9fafb;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }
    .print-card-wrapper h3 {
        font-size: 1rem;
        margin-bottom: 16px;
        color: #333;
    }
    .print-card {
        width: 320px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        padding: 32px 28px 24px;
        box-shadow: 0 2px 16px rgba(0,0,0,0.06);
        border: 2px solid #e5e7eb;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .print-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .print-card .card-icon {
        font-size: 2rem;
        margin-bottom: 6px;
    }
    .print-card .card-heading {
        font-size: 1.15rem;
        font-weight: 800;
        color: #1a1a2e;
        margin-bottom: 4px;
        letter-spacing: 0.5px;
    }
    .print-card .card-subheading {
        font-size: 0.78rem;
        color: #888;
        margin-bottom: 18px;
    }
    .print-card .card-qr {
        background: white;
        display: inline-block;
        padding: 12px;
        border-radius: 14px;
        border: 2px solid #f0f0f0;
    }
    .print-card .card-qr svg,
    .print-card .card-qr img {
        display: block;
        width: 180px;
        height: 180px;
    }
    .print-card .card-store {
        margin-top: 14px;
        font-size: 1rem;
        font-weight: 700;
        color: #333;
    }
    .print-card .card-steps {
        margin-top: 16px;
        text-align: left;
        padding: 14px 16px;
        background: #f8f9ff;
        border-radius: 10px;
    }
    .print-card .card-step {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.78rem;
        color: #555;
        margin-bottom: 6px;
    }
    .print-card .card-step:last-child {
        margin-bottom: 0;
    }
    .print-card .step-num {
        width: 20px;
        height: 20px;
        min-width: 20px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        font-weight: 700;
    }
    .print-card .card-footer-text {
        margin-top: 14px;
        font-size: 0.7rem;
        color: #aaa;
    }

    @media print {
        .navbar, .page-header, .qr-actions, .print-section, .alert,
        .qr-preview, .print-card-wrapper h3, .print-card-wrapper { 
            display: none !important; 
        }
        body { 
            background: white !important; 
            margin: 0 !important;
            padding: 0 !important;
        }
        .container { max-width: 100% !important; padding: 0 !important; }
        .card { box-shadow: none !important; border: none !important; background: none !important; }
        
        .print-card-wrapper {
            display: block !important;
            background: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .print-card {
            display: block !important;
            width: 90mm;
            margin: 15mm auto;
            border: 1px solid #ccc;
            box-shadow: none;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .print-card::before {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .print-card .card-steps {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .print-card .step-num {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
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
            <button onclick="window.print()" class="btn btn-secondary">🖨️ カードを印刷する</button>
        </div>
    </div>

    {{-- 印刷用カードプレビュー --}}
    <div class="print-card-wrapper">
        <h3>🖨️ 印刷プレビュー（席設置用カード）</h3>
        <div class="print-card">
            <div class="card-icon">📱</div>
            <p class="card-heading">口コミにご協力ください</p>
            <p class="card-subheading">QRコードを読み取ってかんたん投稿</p>
            <div class="card-qr">
                {!! $qrCode !!}
            </div>
            <p class="card-store">{{ $store->name }}</p>
            <div class="card-steps">
                <div class="card-step">
                    <span class="step-num">1</span>
                    <span>スマホでQRコードを読み取る</span>
                </div>
                <div class="card-step">
                    <span class="step-num">2</span>
                    <span>満足度とコメントを入力する</span>
                </div>
                <div class="card-step">
                    <span class="step-num">3</span>
                    <span>送信ボタンを押して完了！</span>
                </div>
            </div>
            <p class="card-footer-text">所要時間 約1分 ・ ご感想をお聞かせください</p>
        </div>
    </div>

    <div class="print-section">
        <h3 style="font-size:0.95rem;margin-bottom:8px;">💡 テーブル設置のヒント</h3>
        <div class="print-tip">
            <ul style="padding-left:20px;">
                <li>「🖨️ カードを印刷する」ボタンで上のカードデザインがそのまま印刷できます</li>
                <li>ラミネート加工すると耐久性がアップします</li>
                <li>L判（89×127mm）サイズの用紙に印刷するとテーブルに置きやすいサイズになります</li>
                <li>お客様の目に入りやすい位置（テーブル中央やメニュー立ての横）に設置してください</li>
            </ul>
        </div>
    </div>
</div>
@endsection
