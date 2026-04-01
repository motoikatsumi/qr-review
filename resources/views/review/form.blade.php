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
        gap: 12px;
        margin-bottom: 12px;
    }
    .radio-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .radio-group-label {
        font-size: 0.82rem;
        color: #555;
        font-weight: 500;
        white-space: nowrap;
        min-width: 50px;
    }
    .radio-options {
        display: flex;
        gap: 6px;
        flex: 1;
    }
    .radio-options input[type="radio"] {
        display: none;
    }
    .radio-options label {
        flex: 1;
        text-align: center;
        padding: 8px 0;
        border-radius: 8px;
        border: 2px solid #d1d5db;
        font-size: 0.88rem;
        color: #4b5563;
        background: white;
        cursor: pointer;
        transition: all 0.2s ease;
        font-weight: 500;
    }
    .radio-options input[type="radio"]:checked + label {
        border-color: #667eea;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    .persona-selects select {
        padding: 10px 14px;
        border-radius: 8px;
        border: 2px solid #d1d5db;
        font-size: 0.9rem;
        color: #4b5563;
        background-color: white;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 32px;
        width: 100%;
        outline: none;
        -webkit-appearance: none;
        appearance: none;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
        font-weight: 500;
    }
    .persona-selects select.has-value {
        border-color: #667eea;
        color: #667eea;
        font-weight: 600;
    }
    .persona-selects select.highlight-nudge {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.25);
    }
    .radio-options-age {
        display: flex;
        gap: 5px;
        flex: 1;
    }
    .radio-options-age input[type="radio"] {
        display: none;
    }
    .radio-options-age label {
        flex: 1;
        text-align: center;
        padding: 8px 0;
        border-radius: 8px;
        border: 2px solid #d1d5db;
        font-size: 0.82rem;
        color: #4b5563;
        background: white;
        cursor: pointer;
        transition: all 0.2s ease;
        font-weight: 500;
    }
    @media (max-width: 400px) {
        .radio-group-label {
            min-width: 36px;
            font-size: 0.78rem;
        }
        .radio-options-age {
            gap: 3px;
        }
        .radio-options-age label {
            font-size: 0.74rem;
            padding: 7px 0;
            border-radius: 6px;
        }
    }
    .radio-options-age input[type="radio"]:checked + label {
        border-color: #667eea;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
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
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    .suggestion-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 8px 10px;
        border: 2px solid #667eea;
        background: white;
        color: #667eea;
        border-radius: 24px;
        font-size: 0.82rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: inherit;
        white-space: nowrap;
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
    .suggestion-btn.selected {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-color: transparent;
        box-shadow: 0 2px 8px rgba(102,126,234,0.3);
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
                <div class="radio-group">
                    <span class="radio-group-label">性別</span>
                    <div class="radio-options">
                        <input type="radio" name="gender" id="genderMale" value="男性" {{ old('gender', '男性') == '男性' ? 'checked' : '' }}>
                        <label for="genderMale">男性</label>
                        <input type="radio" name="gender" id="genderFemale" value="女性" {{ old('gender', '男性') == '女性' ? 'checked' : '' }}>
                        <label for="genderFemale">女性</label>
                    </div>
                </div>
                <div class="radio-group">
                    <span class="radio-group-label">来店</span>
                    <div class="radio-options">
                        <input type="radio" name="visit_type" id="visitNew" value="新規" {{ old('visit_type', '新規') == '新規' ? 'checked' : '' }}>
                        <label for="visitNew">新規</label>
                        <input type="radio" name="visit_type" id="visitRepeat" value="リピーター" {{ old('visit_type', '新規') == 'リピーター' ? 'checked' : '' }}>
                        <label for="visitRepeat">リピーター</label>
                    </div>
                </div>
                <div class="radio-group">
                    <span class="radio-group-label">年代</span>
                    <div class="radio-options-age">
                        <input type="radio" name="age" id="age20" value="20" {{ old('age') == '20' ? 'checked' : '' }}>
                        <label for="age20">20代</label>
                        <input type="radio" name="age" id="age30" value="30" {{ old('age') == '30' ? 'checked' : '' }}>
                        <label for="age30">30代</label>
                        <input type="radio" name="age" id="age40" value="40" {{ old('age') == '40' ? 'checked' : '' }}>
                        <label for="age40">40代</label>
                        <input type="radio" name="age" id="age50" value="50" {{ old('age') == '50' ? 'checked' : '' }}>
                        <label for="age50">50代</label>
                        <input type="radio" name="age" id="age60" value="60" {{ old('age') == '60' ? 'checked' : '' }}>
                        <label for="age60">60代~</label>
                    </div>
                </div>
            </div>

            <div class="suggestion-buttons">
                @foreach ($suggestionCategories as $category)
                    @foreach ($category->activeThemes as $theme)
                    <button type="button" class="suggestion-btn" data-keyword="{{ $theme->keyword }}" data-category="{{ $category->id }}">
                        <span class="btn-icon">{{ $theme->icon }}</span>
                        <div class="btn-spinner"></div>
                        {{ $theme->label }}
                    </button>
                    @endforeach
                @endforeach
            </div>
            <p style="font-size: 0.75rem; color: #6b7280; text-align: center; margin-top: 10px;">テーマは複数選択出来ます。選ぶたびに口コミが自動生成されます</p>
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
        // 年代selectの状態復元
        // (年代はラジオボタンなので自動復元)
    })();

    // 提案ボタンをシャッフルして6個表示（各カテゴリから最低1つ選出）
    (function() {
        var container = document.querySelector('.suggestion-buttons');
        var allBtns = Array.from(container.children);
        var maxShow = {{ $themeDisplayCount }};

        // カテゴリごとにグループ化
        var groups = {};
        allBtns.forEach(function(btn) {
            var cat = btn.dataset.category;
            if (!groups[cat]) groups[cat] = [];
            groups[cat].push(btn);
        });

        // 各グループ内をシャッフル
        function shuffle(arr) {
            for (var i = arr.length - 1; i > 0; i--) {
                var j = Math.floor(Math.random() * (i + 1));
                var tmp = arr[i]; arr[i] = arr[j]; arr[j] = tmp;
            }
            return arr;
        }

        var selected = [];
        var remaining = [];
        var catKeys = shuffle(Object.keys(groups));

        // 各カテゴリから1つずつ選出
        catKeys.forEach(function(cat) {
            var shuffled = shuffle(groups[cat].slice());
            selected.push(shuffled[0]);
            remaining = remaining.concat(shuffled.slice(1));
        });

        // 残り枠をランダムで埋める
        shuffle(remaining);
        var slotsLeft = maxShow - selected.length;
        if (slotsLeft > 0) {
            selected = selected.concat(remaining.slice(0, slotsLeft));
        }

        // 選ばれたボタンをシャッフルして並べ替え
        shuffle(selected);
        selected.forEach(function(btn) { container.appendChild(btn); });

        // 全ボタンの表示/非表示を設定
        var selectedSet = new Set(selected);
        allBtns.forEach(function(btn) {
            btn.style.display = selectedSet.has(btn) ? '' : 'none';
        });
    })();

    const suggestUrl = '{{ url("/review/" . $store->slug . "/suggest") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.querySelectorAll('.suggestion-btn').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            // トグル選択
            this.classList.toggle('selected');

            // 選択中のキーワードを収集
            const selectedBtns = document.querySelectorAll('.suggestion-btn.selected');
            const keywords = Array.from(selectedBtns).map(b => b.dataset.keyword);

            // 全て解除された場合は何もしない
            if (keywords.length === 0) {
                return;
            }

            const genderEl = document.querySelector('input[name="gender"]:checked');
            const gender = genderEl ? genderEl.value : '';
            const ageEl = document.querySelector('input[name="age"]:checked');
            const age = ageEl ? ageEl.value : '';
            const visitTypeEl = document.querySelector('input[name="visit_type"]:checked');
            const visitType = visitTypeEl ? visitTypeEl.value : '';

            // ローディング状態
            document.querySelectorAll('.suggestion-btn').forEach(b => b.disabled = true);
            selectedBtns.forEach(b => b.classList.add('loading'));
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
                        keywords: keywords,
                        gender: gender,
                        age: age,
                        visit_type: visitType
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
                document.querySelectorAll('.suggestion-btn').forEach(b => {
                    b.disabled = false;
                    b.classList.remove('loading');
                });
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
        var gender = document.querySelector('input[name="gender"]:checked');
        if (!gender) {
            e.preventDefault();
            alert('性別を選択してください');
            return;
        }
        var visitType = document.querySelector('input[name="visit_type"]:checked');
        if (!visitType) {
            e.preventDefault();
            alert('新規またはリピーターを選択してください');
            return;
        }
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        document.getElementById('submitText').style.display = 'none';
        document.getElementById('loadingSpinner').style.display = 'block';
    });
</script>
@endpush
