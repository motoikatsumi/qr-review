@extends('layouts.review')

@section('title', $store->name . ' - 口コミ投稿')

@push('styles')
<style>
    .store-name {
        font-size: 1.1rem;
        color: #764ba2;
        font-weight: 600;
        text-align: center;
        margin-bottom: 4px;
    }
    .rating-section {
        text-align: center;
        margin: 24px 0;
    }
    .rating-label {
        font-size: 0.9rem;
        color: #555;
        margin-bottom: 12px;
    }
    .stars {
        display: flex;
        justify-content: center;
        gap: 8px;
        direction: rtl;
    }
    .stars input {
        display: none;
    }
    .stars label {
        font-size: 2.8rem;
        color: #ddd;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }
    .stars label:hover,
    .stars label:hover ~ label,
    .stars input:checked ~ label {
        color: #fbbf24;
        transform: scale(1.15);
    }
    .stars label:active {
        transform: scale(0.95);
    }
    .selected-rating {
        text-align: center;
        margin-top: 8px;
        font-size: 0.85rem;
        color: #888;
        min-height: 20px;
    }
    .comment-section {
        margin: 20px 0;
    }
    .comment-section label {
        display: block;
        font-size: 0.9rem;
        color: #555;
        margin-bottom: 8px;
        font-weight: 500;
    }
    .comment-section textarea {
        width: 100%;
        min-height: 120px;
        padding: 14px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        font-family: inherit;
        resize: vertical;
        transition: border-color 0.3s ease;
        outline: none;
    }
    .comment-section textarea:focus {
        border-color: #667eea;
    }
    .comment-section textarea::placeholder {
        color: #bbb;
    }
    .error-msg {
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 6px;
    }
    .submit-section {
        margin-top: 24px;
    }
    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none !important;
    }
    .loading-spinner {
        display: none;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endpush

@section('content')
<div class="card">
    <p class="store-name">{{ $store->name }}</p>
    <h1>ご来店ありがとうございます</h1>
    <p class="subtitle">サービスの感想をお聞かせください</p>

    <form method="POST" action="{{ url('/review/' . $store->slug) }}" id="reviewForm">
        @csrf

        <div class="rating-section">
            <p class="rating-label">満足度を選択してください</p>
            <div class="stars">
                @for ($i = 5; $i >= 1; $i--)
                    <input type="radio" name="rating" id="star{{ $i }}" value="{{ $i }}" {{ old('rating') == $i ? 'checked' : '' }}>
                    <label for="star{{ $i }}" title="{{ $i }}星">★</label>
                @endfor
            </div>
            <p class="selected-rating" id="ratingText"></p>
            @error('rating')
                <p class="error-msg">{{ $message }}</p>
            @enderror
        </div>

        <div class="comment-section">
            <label for="comment">コメント</label>
            <textarea name="comment" id="comment" placeholder="サービスや商品の感想をお聞かせください...">{{ old('comment') }}</textarea>
            @error('comment')
                <p class="error-msg">{{ $message }}</p>
            @enderror
        </div>

        <div class="submit-section">
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <span id="submitText">送信する</span>
                <div class="loading-spinner" id="loadingSpinner"></div>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const ratingLabels = {
        1: 'とても不満',
        2: '不満',
        3: '普通',
        4: '満足',
        5: 'とても満足'
    };

    document.querySelectorAll('.stars input').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.getElementById('ratingText').textContent = 
                this.value + '星 - ' + ratingLabels[this.value];
        });
    });

    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        var rating = document.querySelector('input[name="rating"]:checked');
        if (!rating) {
            e.preventDefault();
            alert('評価を選択してください');
            return;
        }
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        document.getElementById('submitText').style.display = 'none';
        document.getElementById('loadingSpinner').style.display = 'block';
    });
</script>
@endpush
