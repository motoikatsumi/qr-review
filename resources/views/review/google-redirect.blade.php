@extends('layouts.review')

@section('title', 'Googleレビューに投稿 - ' . $store->name)

@push('styles')
<style>
    .success-icon {
        text-align: center;
        font-size: 3rem;
        margin-bottom: 12px;
    }
    .store-name {
        font-size: 1rem;
        color: #764ba2;
        font-weight: 600;
        text-align: center;
        margin-bottom: 4px;
    }
    .ai-section {
        margin: 24px 0;
    }
    .ai-label {
        font-size: 0.85rem;
        color: #888;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .ai-label .badge {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 20px;
        font-weight: 600;
    }
    .ai-text {
        background: #f8f9ff;
        border: 2px solid #e8ebf8;
        border-radius: 12px;
        padding: 16px;
        font-size: 0.95rem;
        line-height: 1.7;
        color: #333;
        position: relative;
    }
    .copy-btn {
        display: block;
        width: 100%;
        margin-top: 12px;
        padding: 12px;
        background: #f0f0f0;
        border: 2px dashed #ccc;
        border-radius: 10px;
        font-size: 0.9rem;
        color: #555;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: inherit;
    }
    .copy-btn:hover {
        background: #e8ebf8;
        border-color: #667eea;
        color: #667eea;
    }
    .copy-btn.copied {
        background: #d1fae5;
        border-color: #34d399;
        color: #059669;
    }
    .divider {
        text-align: center;
        margin: 20px 0;
        position: relative;
    }
    .divider::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        width: 100%;
        height: 1px;
        background: #e5e7eb;
    }
    .divider span {
        background: rgba(255,255,255,0.95);
        padding: 0 12px;
        position: relative;
        color: #888;
        font-size: 0.8rem;
    }
    .steps {
        margin-top: 20px;
    }
    .step {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
    }
    .step-number {
        width: 28px;
        height: 28px;
        min-width: 28px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .step-text {
        font-size: 0.85rem;
        color: #555;
        padding-top: 4px;
    }
    .btn-group {
        margin-top: 24px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="success-icon">📍</div>
    <p class="store-name">{{ $store->name }}</p>
    <h1>あと少しで完了です！</h1>
    <p class="subtitle">Googleマップに口コミを投稿してください</p>

    <div style="margin: 16px 0; padding: 12px 16px; background: linear-gradient(135deg, #fef3c7, #fde68a); border: 2px solid #f59e0b; border-radius: 10px; text-align: center;">
        <p style="margin: 0; font-size: 0.9rem; color: #92400e; font-weight: 700;">⚠️ まだ口コミは投稿されていません</p>
        <p style="margin: 4px 0 0; font-size: 0.78rem; color: #92400e;">下のボタンからGoogleマップを開いて投稿を完了してください</p>
    </div>

    <div class="ai-section">
        <p class="ai-label">
            <span class="badge">AI生成</span>
            Googleマップ用の口コミ文
        </p>
        <div class="ai-text" id="aiText">{{ $aiText }}</div>
        <button type="button" class="copy-btn" id="copyBtn" onclick="copyText()">
            📋 タップしてコピー
        </button>
    </div>

    <div class="divider"><span>投稿手順</span></div>

    <div class="steps">
        <div class="step">
            <span class="step-number">1</span>
            <span class="step-text">上の口コミ文を<strong>コピー</strong>してください</span>
        </div>
        <div class="step">
            <span class="step-number">2</span>
            <span class="step-text">下のボタンから<strong>Googleマップ</strong>を開いてください</span>
        </div>
        <div class="step">
            <span class="step-number">3</span>
            <span class="step-text">口コミ文を<strong>貼り付けて投稿</strong>してください</span>
        </div>
    </div>

    <div class="btn-group">
        <a href="{{ $store->google_review_url }}" target="_blank" class="btn btn-google" id="googleBtn" style="background: linear-gradient(135deg, #4285f4, #34a853); font-size: 1.05rem; font-weight: 700; letter-spacing: 0.5px; animation: pulseBtn 2s ease-in-out infinite;">
            📍 Googleマップを開いて投稿する →
        </a>
        <style>
            @keyframes pulseBtn {
                0%, 100% { box-shadow: 0 4px 15px rgba(66,133,244,0.4); }
                50% { box-shadow: 0 4px 25px rgba(66,133,244,0.7); }
            }
        </style>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function copyText() {
        var text = document.getElementById('aiText').textContent;
        var btn = document.getElementById('copyBtn');

        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                btn.textContent = '✅ コピーしました！';
                btn.classList.add('copied');
            });
        } else {
            // Fallback
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            btn.textContent = '✅ コピーしました！';
            btn.classList.add('copied');
        }

        setTimeout(function() {
            btn.textContent = '📋 タップしてコピー';
            btn.classList.remove('copied');
        }, 3000);
    }
</script>
@endpush
