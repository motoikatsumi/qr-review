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
        margin: 12px 0 8px;
    }
    .rating-label {
        font-size: 0.9rem;
        color: #555;
        margin-bottom: 8px;
    }
    .stars {
        display: flex;
        justify-content: center;
        gap: 4px;
        direction: rtl;
        line-height: 1;
    }
    .stars input {
        display: none;
    }
    .stars label {
        font-size: 2.4rem;
        line-height: 1;
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
        margin: 8px 0 0;
        font-size: 0.78rem;
        color: #888;
        min-height: 0;
        line-height: 1.2;
    }
    .selected-rating:empty {
        display: none;
    }

    /* 提案ボタンエリア */
    .suggestion-section {
        margin: 0 0 12px;
        background: #f9fafb;
        padding: 12px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }

    /* 画像アップロードエリア（折りたたみ式） */
    .image-upload-section {
        margin: 12px 0;
        background: #fffbeb;
        border-radius: 12px;
        border: 1px solid #fde68a;
        overflow: hidden;
    }
    .image-upload-summary {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 14px;
        cursor: pointer;
        font-size: 0.9rem;
        color: #555;
        font-weight: 500;
        user-select: none;
        list-style: none;
    }
    .image-upload-summary::-webkit-details-marker { display: none; }
    .image-upload-summary::marker { content: ''; }
    .image-upload-summary .summary-title {
        flex: 1;
    }
    .image-upload-summary .badge-optional {
        background: #fff;
        color: #92400e;
        font-size: 0.68rem;
        padding: 2px 8px;
        border-radius: 20px;
        border: 1px solid #fcd34d;
        font-weight: 500;
    }
    .image-upload-summary .image-count-badge {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 20px;
        font-weight: 600;
        display: none;
    }
    .image-upload-summary .image-count-badge.show {
        display: inline-block;
    }
    .image-upload-summary .chevron {
        color: #d97706;
        font-size: 0.7rem;
        transition: transform 0.2s ease;
    }
    details[open] .image-upload-summary .chevron {
        transform: rotate(180deg);
    }
    .image-upload-body {
        padding: 0 14px 14px;
    }
    .image-thumbs {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
        gap: 6px;
    }
    .image-thumb {
        position: relative;
        aspect-ratio: 1;
        border-radius: 8px;
        overflow: hidden;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
    }
    .image-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .image-thumb .remove-img {
        position: absolute;
        top: 3px;
        right: 3px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba(0,0,0,0.65);
        color: white;
        border: none;
        cursor: pointer;
        font-size: 0.78rem;
        line-height: 1;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .image-thumb.uploading::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,0.6);
    }
    .image-thumb.uploading .uploading-spinner {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 22px;
        height: 22px;
        margin: -11px 0 0 -11px;
        border: 3px solid rgba(0,0,0,0.15);
        border-top-color: #f59e0b;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        z-index: 1;
    }
    .image-add-btn {
        aspect-ratio: 1;
        border-radius: 8px;
        border: 2px dashed #fcd34d;
        background: white;
        color: #f59e0b;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        font-family: inherit;
        padding: 0;
        gap: 2px;
    }
    .image-add-btn:hover {
        border-color: #f59e0b;
        background: #fffbeb;
        transform: translateY(-1px);
    }
    .image-add-btn .plus {
        font-size: 1.5rem;
        line-height: 1;
        font-weight: 300;
    }
    .image-add-btn .lbl {
        font-size: 0.62rem;
        color: #9ca3af;
        line-height: 1;
    }
    .image-upload-hint {
        font-size: 0.72rem;
        color: #6b7280;
        text-align: center;
        margin-top: 8px;
        line-height: 1.5;
    }
    .image-upload-hint strong {
        color: #d97706;
    }
    .image-error {
        color: #ef4444;
        font-size: 0.78rem;
        margin-top: 6px;
        text-align: center;
        display: none;
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
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        padding-bottom: 2px;
        /* スクロール開始位置の目印 */
        scroll-snap-type: x proximity;
    }
    .radio-options::-webkit-scrollbar {
        height: 3px;
    }
    .radio-options::-webkit-scrollbar-thumb {
        background: rgba(102,126,234,0.35);
        border-radius: 2px;
    }
    .radio-options input[type="radio"] {
        display: none;
    }
    .radio-options label {
        flex: 1 0 auto;
        min-width: 60px;
        white-space: nowrap;
        text-align: center;
        padding: 8px 14px;
        border-radius: 8px;
        border: 2px solid #d1d5db;
        font-size: 0.88rem;
        color: #4b5563;
        background: white;
        cursor: pointer;
        transition: all 0.2s ease;
        font-weight: 500;
        scroll-snap-align: start;
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
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        padding-bottom: 2px;
    }
    .radio-options-age::-webkit-scrollbar {
        height: 3px;
    }
    .radio-options-age::-webkit-scrollbar-thumb {
        background: rgba(102,126,234,0.35);
        border-radius: 2px;
    }
    .radio-options-age input[type="radio"] {
        display: none;
    }
    .radio-options-age label {
        flex: 1 0 auto;
        min-width: 52px;
        white-space: nowrap;
        text-align: center;
        padding: 8px 10px;
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
            padding: 7px 10px;
            min-width: 48px;
            border-radius: 6px;
        }
        .radio-options label {
            font-size: 0.82rem;
            padding: 7px 12px;
            min-width: 56px;
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
        margin: 0 0 10px;
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
                {{-- 業種マスタの review_option_groups に定義された順に表示 --}}
                @foreach ($reviewGroups as $groupIdx => $group)
                    @php
                        $gKey = $group['key'] ?? ('q' . $groupIdx);
                        $gLabel = $group['label'] ?? $gKey;
                        $gOptions = $group['options'] ?? [];
                        $allowOther = !empty($group['allow_other_input']);
                        if (empty($gOptions) && !$allowOther) continue;
                        // allow_other_input の場合はデフォルト未選択（空欄送信を許容）。それ以外は従来どおり最初の選択肢を初期値に。
                        $oldValue = old($gKey, $allowOther ? '' : ($gOptions[0] ?? ''));
                        $oldOther = old($gKey . '_other', '');
                        $isOtherSelected = $allowOther && $oldValue === 'その他';
                    @endphp
                    <div class="radio-group">
                        <span class="radio-group-label">{{ $gLabel }}</span>
                        <div class="radio-options">
                            @foreach ($gOptions as $optIdx => $optVal)
                                @php $oid = 'opt_' . $gKey . '_' . $optIdx; @endphp
                                <input type="radio" name="{{ $gKey }}" id="{{ $oid }}" value="{{ $optVal }}" {{ $oldValue == $optVal ? 'checked' : '' }}>
                                <label for="{{ $oid }}">{{ $optVal }}</label>
                            @endforeach
                            @if ($allowOther)
                                @php $oidOther = 'opt_' . $gKey . '_other'; @endphp
                                <input type="radio" name="{{ $gKey }}" id="{{ $oidOther }}" value="その他" {{ $isOtherSelected ? 'checked' : '' }}>
                                <label for="{{ $oidOther }}">その他</label>
                            @endif
                        </div>
                    </div>
                    @if ($allowOther)
                        <div class="other-input-wrap" data-group-key="{{ $gKey }}" style="display: {{ $isOtherSelected ? 'block' : 'none' }}; margin-top: -4px;">
                            <input type="text" name="{{ $gKey }}_other" value="{{ $oldOther }}" maxlength="50"
                                   placeholder="選択肢にない場合はこちらに自由入力（任意）"
                                   style="width:100%;padding:9px 12px;border:2px solid #d1d5db;border-radius:8px;font-size:0.9rem;color:#4b5563;outline:none;font-family:inherit;">
                        </div>
                    @endif
                @endforeach
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
            <p style="font-size: 0.75rem; color: #6b7280; text-align: center; margin-top: 10px;">💡 テーマを選ぶと口コミが自動作成されます。<br>テーマは複数個選択可能です。</p>
            <p class="ai-error" id="aiError">文章の生成に失敗しました。もう一度お試しください。</p>
        </div>

        {{-- 画像アップロード（折りたたみ） --}}
        <details class="image-upload-section" id="imageUploadSection" {{ !empty($existingImages) ? 'open' : '' }}>
            <summary class="image-upload-summary">
                <span class="summary-title">📷 写真を追加</span>
                <span class="image-count-badge {{ !empty($existingImages) ? 'show' : '' }}" id="imageCountBadge">{{ !empty($existingImages) ? count($existingImages) . '枚' : '' }}</span>
                <span class="badge-optional">任意・最大5枚</span>
                <span class="chevron">▼</span>
            </summary>
            <div class="image-upload-body">
                <div class="image-thumbs" id="imageThumbs">
                    @foreach (($existingImages ?? []) as $img)
                        <div class="image-thumb" data-filename="{{ $img['filename'] }}">
                            <img src="{{ $img['url'] }}" alt="">
                            <button type="button" class="remove-img" aria-label="削除">×</button>
                            <input type="hidden" name="uploaded_images[]" value="{{ $img['filename'] }}">
                        </div>
                    @endforeach
                    <button type="button" class="image-add-btn" id="imageAddBtn">
                        <span class="plus">+</span>
                        <span class="lbl">追加</span>
                    </button>
                </div>
                <input type="file" id="imageFileInput" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" multiple style="display:none;">
                <p class="image-upload-hint">
                    💡 写真があると <strong>Google検索でお店が目立ちやすく</strong> なります🌟<br>
                    <span style="color:#9a3412;">📱 iPhoneの方は <strong>カメラアプリで撮影</strong> → 「写真ライブラリから選択」がおすすめ</span><br>
                    ※5MBまで・jpg / png / webp / heic
                </p>
                <p class="image-error" id="imageError"></p>
            </div>
        </details>

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

    // 質問項目ラジオの「再クリックで解除」+「その他」入力欄のトグル
    (function() {
        var personaSelects = document.querySelector('.persona-selects');
        if (!personaSelects) return;

        // 「その他」入力欄の表示制御
        function syncOtherInputs() {
            personaSelects.querySelectorAll('.other-input-wrap').forEach(function(wrap) {
                var key = wrap.dataset.groupKey;
                var otherRadio = personaSelects.querySelector('input[type="radio"][name="' + key + '"][value="その他"]');
                var visible = !!(otherRadio && otherRadio.checked);
                wrap.style.display = visible ? 'block' : 'none';
                if (!visible) {
                    var input = wrap.querySelector('input[type="text"]');
                    if (input) input.value = '';
                }
            });
        }
        syncOtherInputs();

        // ラベルクリック時：選択済みラジオならクリック後に解除する
        personaSelects.querySelectorAll('label').forEach(function(label) {
            var wasChecked = false;
            function captureState() {
                var forId = label.getAttribute('for');
                var radio = forId ? document.getElementById(forId) : null;
                wasChecked = !!(radio && radio.checked);
            }
            label.addEventListener('mousedown', captureState);
            label.addEventListener('touchstart', captureState, { passive: true });
            label.addEventListener('click', function() {
                var forId = label.getAttribute('for');
                var radio = forId ? document.getElementById(forId) : null;
                if (!radio) return;
                if (wasChecked) {
                    // ブラウザは click 時に既にチェック状態にしているので、こちらで外す
                    radio.checked = false;
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                }
                wasChecked = false;
                syncOtherInputs();
            });
        });

        // ラジオの change 全般を監視（プログラムからの変更にも反応）
        personaSelects.addEventListener('change', function(e) {
            if (e.target && e.target.matches('input[type="radio"]')) {
                syncOtherInputs();
            }
        });
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
    const uploadImageUrl = '{{ url("/review/" . $store->slug . "/upload-image") }}';
    const deleteImageUrl = '{{ url("/review/" . $store->slug . "/upload-image") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const MAX_IMAGES = 5;

    // ===== 画像アップロード機能 =====
    (function() {
        const thumbs = document.getElementById('imageThumbs');
        const addBtn = document.getElementById('imageAddBtn');
        const fileInput = document.getElementById('imageFileInput');
        const errorEl = document.getElementById('imageError');

        const countBadge = document.getElementById('imageCountBadge');

        function getImageCount() {
            return thumbs.querySelectorAll('.image-thumb').length;
        }

        function updateAddBtnVisibility() {
            addBtn.style.display = getImageCount() >= MAX_IMAGES ? 'none' : '';
        }

        function updateCountBadge() {
            const c = getImageCount();
            if (c > 0) {
                countBadge.textContent = c + '枚';
                countBadge.classList.add('show');
            } else {
                countBadge.textContent = '';
                countBadge.classList.remove('show');
            }
        }

        function showError(msg) {
            errorEl.textContent = msg;
            errorEl.style.display = 'block';
            setTimeout(() => { errorEl.style.display = 'none'; }, 4000);
        }

        addBtn.addEventListener('click', function() {
            errorEl.style.display = 'none';
            fileInput.value = '';
            fileInput.click();
        });

        fileInput.addEventListener('change', async function() {
            const files = Array.from(fileInput.files || []);
            if (!files.length) return;

            const remaining = MAX_IMAGES - getImageCount();
            const targets = files.slice(0, remaining);
            if (files.length > remaining) {
                showError('画像は最大' + MAX_IMAGES + '枚までです。');
            }

            for (const file of targets) {
                await uploadOne(file);
            }
        });

        async function uploadOne(file) {
            // プレビュー（uploading 状態）を先に追加
            const thumb = document.createElement('div');
            thumb.className = 'image-thumb uploading';
            const previewUrl = URL.createObjectURL(file);
            thumb.innerHTML = '<img src="' + previewUrl + '" alt=""><div class="uploading-spinner"></div>';
            thumbs.insertBefore(thumb, addBtn);
            updateAddBtnVisibility();
            updateCountBadge();

            const fd = new FormData();
            fd.append('image', file);

            try {
                const res = await fetch(uploadImageUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: fd
                });

                let data = {};
                try { data = await res.json(); } catch (_) {}

                if (!res.ok) {
                    const msg = (data && data.message) || (data && data.error) ||
                        (data && data.errors && data.errors.image && data.errors.image[0]) ||
                        'アップロードに失敗しました';
                    throw new Error(msg);
                }

                URL.revokeObjectURL(previewUrl);
                thumb.classList.remove('uploading');
                thumb.dataset.filename = data.filename;
                thumb.innerHTML =
                    '<img src="' + data.url + '" alt="">' +
                    '<button type="button" class="remove-img" aria-label="削除">×</button>' +
                    '<input type="hidden" name="uploaded_images[]" value="' + data.filename + '">';

            } catch (e) {
                URL.revokeObjectURL(previewUrl);
                thumb.remove();
                showError(e.message || 'アップロードに失敗しました');
                updateAddBtnVisibility();
            updateCountBadge();
            }
        }

        // 削除（イベント委譲）
        thumbs.addEventListener('click', async function(e) {
            const btn = e.target.closest('.remove-img');
            if (!btn) return;
            const thumb = btn.closest('.image-thumb');
            if (!thumb) return;
            const filename = thumb.dataset.filename;

            // 楽観的に即時削除
            thumb.remove();
            updateAddBtnVisibility();
            updateCountBadge();

            if (!filename) return;
            try {
                await fetch(deleteImageUrl, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ filename: filename })
                });
            } catch (_) {
                // ネットワーク失敗時もUI上は削除済み（送信時に getValid... で除外される）
            }
        });

        updateAddBtnVisibility();
        updateCountBadge();
    })();

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

            // persona: 動的に各 radio-group の選択値を集める
            const personaBody = { keywords: keywords };
            document.querySelectorAll('.persona-selects input[type="radio"]:checked').forEach(inp => {
                personaBody[inp.name] = inp.value;
            });
            // 「その他」が選ばれているグループは <key>_other の自由入力も送る
            document.querySelectorAll('.persona-selects .other-input-wrap').forEach(wrap => {
                const key = wrap.dataset.groupKey;
                if (!key) return;
                const otherRadio = document.querySelector('.persona-selects input[type="radio"][name="' + key + '"][value="その他"]');
                if (otherRadio && otherRadio.checked) {
                    const textInput = wrap.querySelector('input[type="text"]');
                    const v = textInput ? textInput.value.trim() : '';
                    if (v) personaBody[key + '_other'] = v;
                }
            });

            // アップロード済み画像（あれば AI のヒントとして渡す）
            const uploadedFilenames = Array.from(document.querySelectorAll('input[name="uploaded_images[]"]')).map(i => i.value);
            if (uploadedFilenames.length > 0) {
                personaBody.uploaded_images = uploadedFilenames;
            }

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
                    body: JSON.stringify(personaBody)
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
