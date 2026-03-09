@extends('layouts.review')

@section('title', '入力内容の確認 - ' . $store->name)

@push('styles')
<style>
    .store-name {
        font-size: 1rem;
        color: #764ba2;
        font-weight: 600;
        text-align: center;
        margin-bottom: 4px;
    }
    .confirm-section {
        margin: 20px 0;
    }
    .confirm-item {
        background: #f8f9ff;
        border: 1px solid #e8ebf8;
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 12px;
    }
    .confirm-label {
        font-size: 0.78rem;
        color: #888;
        margin-bottom: 6px;
        font-weight: 500;
    }
    .confirm-value {
        font-size: 0.95rem;
        color: #333;
        line-height: 1.7;
    }
    .confirm-stars {
        color: #fbbf24;
        font-size: 1.5rem;
        letter-spacing: 2px;
    }
    .confirm-stars .empty {
        color: #ddd;
    }
    .google-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .google-badge.yes {
        background: linear-gradient(135deg, #4285f4, #34a853);
        color: white;
    }
    .google-badge.no {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    .btn-group {
        margin-top: 24px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .btn-back {
        background: #f0f0f0;
        color: #555;
        border: none;
        cursor: pointer;
    }
    .btn-back:hover {
        background: #e0e0e0;
        transform: translateY(-1px);
    }
    .google-flow-notice {
        margin: 20px 0 0;
        padding: 14px 16px;
        background: linear-gradient(135deg, #dbeafe, #dcfce7);
        border: 2px solid #60a5fa;
        border-radius: 12px;
        text-align: center;
    }
    .google-flow-notice .notice-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e40af;
        margin-bottom: 6px;
    }
    .google-flow-notice .notice-desc {
        font-size: 0.8rem;
        color: #1e3a5f;
        line-height: 1.6;
        margin: 0;
    }
    .btn-google-next {
        background: linear-gradient(135deg, #4285f4, #34a853) !important;
        color: white !important;
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: 0.5px;
    }
</style>
@endpush

@section('content')
<div class="card">
    <p class="store-name">{{ $store->name }}</p>
    <h1>入力内容の確認</h1>
    <p class="subtitle">以下の内容でよろしいですか？</p>

    <div class="confirm-section">
        <div class="confirm-item">
            <p class="confirm-label">満足度</p>
            <p class="confirm-stars">
                @for ($i = 1; $i <= 5; $i++)
                    <span class="{{ $i <= $rating ? '' : 'empty' }}">★</span>
                @endfor
            </p>
        </div>

        <div class="confirm-item">
            <p class="confirm-label">コメント</p>
            <p class="confirm-value">{{ $comment }}</p>
        </div>

        @if ($rating >= 4)
        <div class="confirm-item">
            <p class="confirm-label">Googleアカウント</p>
            <p class="confirm-value">
                @if ($has_google_account === '1')
                    <span class="google-badge yes">✅ 持っている</span>
                @else
                    <span class="google-badge no">❌ 持っていない</span>
                @endif
            </p>
        </div>
        @endif

        @if ($rating >= 4 && $has_google_account === '1')
        <div class="google-flow-notice">
            <p class="notice-title">📍 次のステップ：Googleマップで口コミ投稿</p>
            <p class="notice-desc">
                「次に進む」を押すと、<strong>Googleマップの口コミ投稿画面</strong>へご案内します。<br>
                AIが作成した口コミ文をコピーして貼り付けるだけでOKです！
            </p>
        </div>
        @endif
    </div>

    <div class="btn-group">
        {{-- 送信フォーム --}}
        <form method="POST" action="{{ url('/review/' . $store->slug) }}" id="submitForm">
            @csrf
            <input type="hidden" name="rating" value="{{ $rating }}">
            <input type="hidden" name="comment" value="{{ $comment }}">
            <input type="hidden" name="is_ai_generated" value="{{ $is_ai_generated }}">
            <input type="hidden" name="has_google_account" value="{{ $has_google_account }}">
            <input type="hidden" name="gender" value="{{ $gender }}">
            <input type="hidden" name="age" value="{{ $age }}">
            <button type="submit" class="btn btn-primary{{ ($rating >= 4 && $has_google_account === '1') ? ' btn-google-next' : '' }}" id="submitBtn">
                <span id="submitText">{{ ($rating >= 4 && $has_google_account === '1') ? 'Googleマップへ進む →' : '次に進む →' }}</span>
                <div class="loading-spinner" id="loadingSpinner" style="display:none;width:20px;height:20px;border:3px solid rgba(255,255,255,0.3);border-top-color:white;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto;"></div>
            </button>
        </form>

        {{-- 修正フォーム --}}
        <form method="POST" action="{{ url('/review/' . $store->slug . '/confirm') }}">
            @csrf
            <input type="hidden" name="_back" value="1">
            <input type="hidden" name="rating" value="{{ $rating }}">
            <input type="hidden" name="comment" value="{{ $comment }}">
            <input type="hidden" name="is_ai_generated" value="{{ $is_ai_generated }}">
            <input type="hidden" name="has_google_account" value="{{ $has_google_account }}">
            <input type="hidden" name="gender" value="{{ $gender }}">
            <input type="hidden" name="age" value="{{ $age }}">
            <button type="submit" class="btn btn-back">✏️ 修正する</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('submitForm').addEventListener('submit', function() {
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        document.getElementById('submitText').style.display = 'none';
        document.getElementById('loadingSpinner').style.display = 'block';
    });
</script>
<style>
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
@endpush
