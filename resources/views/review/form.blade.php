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
        gap: 4px;
        direction: rtl;
    }
    .stars input {
        display: none;
    }
    .stars label {
        font-size: 2.4rem;
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

    /* 提案ボタンエリア */
    .suggestion-section {
        margin: 20px 0 12px;
        background: #f9fafb;
        padding: 16px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }
    .persona-selects {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 8px;
        margin-bottom: 12px;
    }
    @media (min-width: 380px) {
        .persona-selects {
            flex-direction: row;
            gap: 12px;
        }
    }
    .persona-selects select {
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: 0.9rem;
        color: #4b5563;
        background-color: white;
        flex: 1;
        min-width: 0;
        outline: none;
        -webkit-appearance: none;
        appearance: none;
    }
    .persona-selects select:focus {
        border-color: #667eea;
    }

    .suggestion-label {
        font-size: 0.85rem;
        color: #555;
        margin-bottom: 10px;
        font-weight: 500;
    }
    .suggestion-label span {
        display: inline-block;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        font-size: 0.72rem;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: 6px;
        vertical-align: middle;
    }
    .suggestion-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
    }
    .suggestion-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 14px;
        border: 2px solid #667eea;
        background: white;
        color: #667eea;
        border-radius: 24px;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: inherit;
        white-space: nowrap;
        max-width: 100%;
    }
    .suggestion-btn:hover {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-color: transparent;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102,126,234,0.35);
    }
    .suggestion-btn:active {
        transform: translateY(0);
    }
    .suggestion-btn.loading {
        background: #f3f4f6;
        color: #aaa;
        border-color: #e5e7eb;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }
    .suggestion-btn .btn-spinner {
        display: none;
        width: 13px;
        height: 13px;
        border: 2px solid rgba(0,0,0,0.15);
        border-top-color: #aaa;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    .suggestion-btn.loading .btn-spinner {
        display: inline-block;
    }
    .suggestion-btn.loading .btn-icon {
        display: none;
    }

    .comment-section {
        margin: 12px 0 20px;
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
        -webkit-appearance: none;
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

    .ai-hint {
        font-size: 0.78rem;
        color: #9ca3af;
        margin-top: 6px;
    }
    .ai-error {
        font-size: 0.8rem;
        color: #ef4444;
        margin-top: 6px;
        display: none;
    }

</style>
@endpush

@section('content')
<div class="card">
    <p class="store-name">{{ $store->name }}</p>
    <h1>ご来店ありがとうございます</h1>
    <p class="subtitle">サービスの感想をお聞かせください</p>

    <form method="POST" action="{{ url('/review/' . $store->slug . '/confirm') }}" id="reviewForm">
        @csrf
        <input type="hidden" name="is_ai_generated" id="is_ai_generated" value="{{ old('is_ai_generated', 0) }}">
        <input type="hidden" name="gender" id="genderHidden" value="{{ old('gender', '') }}">
        <input type="hidden" name="age" id="ageHidden" value="{{ old('age', '') }}">

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

        {{-- 口コミ提案ボタン --}}
        <div class="suggestion-section">
            <p class="suggestion-label">
                口コミのテーマを選ぶ
                <span>✨ AIが自動入力</span>
            </p>
            
            <div class="persona-selects">
                <select id="genderSelect" onchange="document.getElementById('genderHidden').value=this.value">
                    <option value="">性別を選択（任意）</option>
                    <option value="男性" {{ old('gender') == '男性' ? 'selected' : '' }}>男性</option>
                    <option value="女性" {{ old('gender') == '女性' ? 'selected' : '' }}>女性</option>
                </select>
                <select id="ageSelect" onchange="document.getElementById('ageHidden').value=this.value">
                    <option value="">年代を選択（任意）</option>
                    <option value="20" {{ old('age') == '20' ? 'selected' : '' }}>20代</option>
                    <option value="30" {{ old('age') == '30' ? 'selected' : '' }}>30代</option>
                    <option value="40" {{ old('age') == '40' ? 'selected' : '' }}>40代</option>
                    <option value="50" {{ old('age') == '50' ? 'selected' : '' }}>50代</option>
                    <option value="60" {{ old('age') == '60' ? 'selected' : '' }}>60代以上</option>
                </select>
            </div>

            <div class="suggestion-buttons">
                <button type="button" class="suggestion-btn" data-keyword="査定価格が高くて満足、高価買取">
                    <span class="btn-icon">💰</span>
                    <div class="btn-spinner"></div>
                    高価買取
                </button>
                <button type="button" class="suggestion-btn" data-keyword="査定が丁寧でわかりやすく親切に説明してくれた">
                    <span class="btn-icon">📋</span>
                    <div class="btn-spinner"></div>
                    丁寧な査定
                </button>
                <button type="button" class="suggestion-btn" data-keyword="スタッフの対応が親切で感じが良かった">
                    <span class="btn-icon">😊</span>
                    <div class="btn-spinner"></div>
                    スタッフが親切
                </button>
                <button type="button" class="suggestion-btn" data-keyword="査定がスムーズで待ち時間が短く素早く対応してもらえた">
                    <span class="btn-icon">⚡</span>
                    <div class="btn-spinner"></div>
                    素早い対応
                </button>
                <button type="button" class="suggestion-btn" data-keyword="また利用したい、リピートしたい、おすすめできる">
                    <span class="btn-icon">🤝</span>
                    <div class="btn-spinner"></div>
                    また利用したい
                </button>
                <button type="button" class="suggestion-btn" data-keyword="買取金額に満足した、思った以上の金額で嬉しかった、納得の価格">
                    <span class="btn-icon">💴</span>
                    <div class="btn-spinner"></div>
                    金額に満足
                </button>
            </div>
            <p class="ai-error" id="aiError">文章の生成に失敗しました。もう一度お試しください。</p>
        </div>

        <div class="comment-section">
            <label for="comment">コメント</label>
            <p class="ai-hint" id="aiHint">※ AIが生成した文章は自由に編集できます</p>
            <textarea name="comment" id="comment" placeholder="上のボタンでテーマを選ぶか、直接ご感想をお書きください...">{{ old('comment') }}</textarea>
            @error('comment')
                <p class="error-msg">{{ $message }}</p>
            @enderror
        </div>

        <div class="submit-section">
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <span id="submitText">確認</span>
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

    // ページ読み込み時の状態復元（修正して戻った場合）
    (function() {
        var checkedRating = document.querySelector('input[name="rating"]:checked');
        if (checkedRating) {
            document.getElementById('ratingText').textContent =
                checkedRating.value + '星 - ' + ratingLabels[checkedRating.value];
        }
    })();
    const suggestUrl = '{{ url("/review/" . $store->slug . "/suggest") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.querySelectorAll('.suggestion-btn').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const keyword = this.dataset.keyword;
            const gender = document.getElementById('genderSelect').value;
            const age = document.getElementById('ageSelect').value;

            // ローディング状態
            document.querySelectorAll('.suggestion-btn').forEach(b => b.disabled = true);
            this.classList.add('loading');
            document.getElementById('aiError').style.display = 'none';

            try {
                const res = await fetch(suggestUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        keyword: keyword,
                        gender: gender,
                        age: age
                    })
                });

                const data = await res.json();

                if (!res.ok || data.error) {
                    throw new Error(data.error || '生成エラー');
                }

                document.getElementById('comment').value = data.text;
                document.getElementById('is_ai_generated').value = '1';
                document.getElementById('comment').focus();
                document.getElementById('aiHint').style.display = 'block';

            } catch (e) {
                document.getElementById('aiError').style.display = 'block';
            } finally {
                // ローディング解除
                document.querySelectorAll('.suggestion-btn').forEach(b => b.disabled = false);
                this.classList.remove('loading');
            }
        });
    });

    document.getElementById('comment').addEventListener('input', function() {
        // もしユーザーが手動で全て消したら、AI生成フラグをリセットしてもいいかもしれないが、
        // 基本的には「AIを使ってベースを作ったか」のトラッキングなのでそのままにしておく
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
