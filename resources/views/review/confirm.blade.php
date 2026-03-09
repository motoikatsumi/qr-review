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

    /* Google アカウント確認セクション */
    .google-section {
        margin-top: 16px;
        padding: 18px 16px;
        background: linear-gradient(135deg, #f0f7ff, #f0fdf4);
        border: 2px solid #93c5fd;
        border-radius: 14px;
    }
    .google-section-title {
        font-size: 0.92rem;
        font-weight: 700;
        color: #1e40af;
        text-align: center;
        margin-bottom: 4px;
    }
    .google-section-desc {
        font-size: 0.78rem;
        color: #555;
        text-align: center;
        margin-bottom: 14px;
        line-height: 1.5;
    }
    .google-signin-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        padding: 14px;
        background: white;
        border: 2px solid #4285f4;
        border-radius: 12px;
        font-size: 0.95rem;
        font-weight: 600;
        color: #333;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .google-signin-btn:hover {
        background: #f8faff;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(66, 133, 244, 0.25);
    }
    .google-signin-btn img {
        width: 22px;
        height: 22px;
    }
    .google-no-account {
        display: block;
        text-align: center;
        margin-top: 10px;
        font-size: 0.82rem;
        color: #666;
        cursor: pointer;
        text-decoration: underline;
        background: none;
        border: none;
        width: 100%;
    }
    .google-no-account:hover {
        color: #764ba2;
    }
    .google-confirmed {
        display: none;
        text-align: center;
        padding: 12px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .google-confirmed.yes {
        background: linear-gradient(135deg, #dcfce7, #dbeafe);
        color: #166534;
        border: 2px solid #86efac;
    }
    .google-confirmed.no {
        background: linear-gradient(135deg, #f3e8ff, #fce7f3);
        color: #6b21a8;
        border: 2px solid #d8b4fe;
    }
    .google-change-btn {
        display: none;
        margin: 8px auto 0;
        font-size: 0.78rem;
        color: #888;
        cursor: pointer;
        text-decoration: underline;
        background: none;
        border: none;
    }
    .google-change-btn:hover {
        color: #764ba2;
    }
    .google-validation-error {
        display: none;
        color: #dc2626;
        font-size: 0.82rem;
        text-align: center;
        margin-top: 8px;
        font-weight: 600;
    }

    /* Google投稿セクション（アカウント確認後に表示） */
    .google-post-section {
        display: none;
        margin-top: 16px;
    }
    .ai-section {
        margin-bottom: 16px;
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
    }
    .copy-btn {
        display: block;
        width: 100%;
        margin-top: 10px;
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
    .google-post-notice {
        margin-top: 12px;
        padding: 10px 14px;
        background: linear-gradient(135deg, #dbeafe, #dcfce7);
        border: 2px solid #60a5fa;
        border-radius: 10px;
        text-align: center;
    }
    .google-post-notice p {
        margin: 0;
        font-size: 0.8rem;
        color: #1e3a5f;
        line-height: 1.5;
    }
    .btn-google-submit {
        background: linear-gradient(135deg, #4285f4, #34a853) !important;
        color: white !important;
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: 0.5px;
        animation: pulseBtn 2s ease-in-out infinite;
    }
    @keyframes pulseBtn {
        0%, 100% { box-shadow: 0 4px 15px rgba(66,133,244,0.4); }
        50% { box-shadow: 0 4px 25px rgba(66,133,244,0.7); }
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

        {{-- 高評価の場合のみ Google アカウント確認セクション表示 --}}
        @if ($rating >= 4)
        <div class="google-section" id="googleSection">
            <p class="google-section-title">📱 Googleアカウントをお持ちですか？</p>
            <p class="google-section-desc">Googleマップへの口コミ投稿にご協力ください</p>

            <div id="googleButtons">
                <button type="button" class="google-signin-btn" id="googleSignInBtn" onclick="tryGoogleSignIn()">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
                    Googleアカウントで確認
                </button>
                <button type="button" class="google-no-account" id="googleNoBtn" onclick="selectNoGoogle()">
                    Googleアカウントを持っていない
                </button>
            </div>

            <div class="google-confirmed yes" id="googleYesConfirmed">
                ✅ Googleアカウント確認済み
            </div>
            <div class="google-confirmed no" id="googleNoConfirmed">
                Googleアカウントなしで送信します
            </div>
            <button type="button" class="google-change-btn" id="googleChangeBtn" onclick="resetGoogleSelection()">
                選択を変更する
            </button>

            <p class="google-validation-error" id="googleError">
                Googleアカウントの確認を選択してください
            </p>
        </div>

        {{-- Googleアカウント確認後に表示：口コミ文コピーセクション --}}
        <div class="google-post-section" id="googlePostSection">
            <div class="ai-section">
                <p class="ai-label">
                    <span class="badge">AI生成</span>
                    Googleマップ用の口コミ文
                </p>
                <div class="ai-text" id="aiText">{{ $comment }}</div>
                <button type="button" class="copy-btn" id="copyBtn" onclick="copyText()">
                    📋 タップしてコピー
                </button>
            </div>

            <div class="google-post-notice">
                <p>📍 下のボタンを押すと口コミ文が<strong>自動コピー</strong>され、<strong>Googleマップ</strong>が開きます</p>
            </div>
        </div>
        @endif
    </div>

    <div class="btn-group">
        {{-- 送信フォーム --}}
        <form method="POST" action="{{ url('/review/' . $store->slug) }}" id="submitForm">
            @csrf
            <input type="hidden" name="submit_token" value="{{ $submitToken }}">
            <input type="hidden" name="rating" value="{{ $rating }}">
            <input type="hidden" name="comment" value="{{ $comment }}">
            <input type="hidden" name="is_ai_generated" value="{{ $is_ai_generated }}">
            <input type="hidden" name="has_google_account" id="hasGoogleAccount" value="">
            <input type="hidden" name="gender" value="{{ $gender }}">
            <input type="hidden" name="age" value="{{ $age }}">
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <span id="submitText">送信する →</span>
                <div class="loading-spinner" id="loadingSpinner" style="display:none;width:20px;height:20px;border:3px solid rgba(255,255,255,0.3);border-top-color:white;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto;"></div>
            </button>
        </form>
        @if ($rating >= 4)
        <input type="hidden" id="googleReviewUrl" value="{{ $store->google_review_url }}">
        @endif

        {{-- 修正フォーム --}}
        <form method="POST" action="{{ url('/review/' . $store->slug . '/confirm') }}">
            @csrf
            <input type="hidden" name="_back" value="1">
            <input type="hidden" name="rating" value="{{ $rating }}">
            <input type="hidden" name="comment" value="{{ $comment }}">
            <input type="hidden" name="is_ai_generated" value="{{ $is_ai_generated }}">
            <input type="hidden" name="gender" value="{{ $gender }}">
            <input type="hidden" name="age" value="{{ $age }}">
            <button type="submit" class="btn btn-back">✏️ 修正する</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
    var rating = {{ $rating }};
    var googleClientId = '{{ config("services.google.client_id") }}';
    var googleSelected = false;

    // Google Sign-In 初期化
    function initGoogleClient() {
        if (typeof google === 'undefined' || !google.accounts) {
            setTimeout(initGoogleClient, 200);
            return;
        }
        google.accounts.id.initialize({
            client_id: googleClientId,
            callback: handleGoogleResponse,
            auto_select: false,
            cancel_on_tap_outside: true
        });
    }

    // Google Sign-In 試行
    function tryGoogleSignIn() {
        if (typeof google === 'undefined' || !google.accounts) {
            selectGoogleAccount();
            return;
        }
        google.accounts.id.prompt(function(notification) {
            if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
                selectGoogleAccount();
            }
        });
    }

    // Google レスポンス処理
    function handleGoogleResponse(response) {
        if (response.credential) {
            selectGoogleAccount();
        }
    }

    // Google アカウントあり選択
    function selectGoogleAccount() {
        googleSelected = true;
        document.getElementById('hasGoogleAccount').value = '1';
        document.getElementById('googleButtons').style.display = 'none';
        document.getElementById('googleYesConfirmed').style.display = 'block';
        document.getElementById('googleNoConfirmed').style.display = 'none';
        document.getElementById('googleChangeBtn').style.display = 'block';
        document.getElementById('googleError').style.display = 'none';
        // Google投稿セクション表示
        document.getElementById('googlePostSection').style.display = 'block';
        // ボタンをGoogle用に変更
        var btn = document.getElementById('submitBtn');
        btn.classList.add('btn-google-submit');
        document.getElementById('submitText').textContent = '📍 コピーしてGoogleマップへ →';
    }

    // Google アカウントなし選択
    function selectNoGoogle() {
        googleSelected = true;
        document.getElementById('hasGoogleAccount').value = '0';
        document.getElementById('googleButtons').style.display = 'none';
        document.getElementById('googleYesConfirmed').style.display = 'none';
        document.getElementById('googleNoConfirmed').style.display = 'block';
        document.getElementById('googleChangeBtn').style.display = 'block';
        document.getElementById('googleError').style.display = 'none';
        // Google投稿セクション非表示
        document.getElementById('googlePostSection').style.display = 'none';
        var btn = document.getElementById('submitBtn');
        btn.classList.remove('btn-google-submit');
        document.getElementById('submitText').textContent = '送信する →';
    }

    // 選択リセット
    function resetGoogleSelection() {
        googleSelected = false;
        document.getElementById('hasGoogleAccount').value = '';
        document.getElementById('googleButtons').style.display = 'block';
        document.getElementById('googleYesConfirmed').style.display = 'none';
        document.getElementById('googleNoConfirmed').style.display = 'none';
        document.getElementById('googleChangeBtn').style.display = 'none';
        document.getElementById('googleError').style.display = 'none';
        // Google投稿セクション非表示
        document.getElementById('googlePostSection').style.display = 'none';
        var btn = document.getElementById('submitBtn');
        btn.classList.remove('btn-google-submit');
        document.getElementById('submitText').textContent = '送信する →';
    }

    // テキストコピー
    function copyText() {
        var text = document.getElementById('aiText').textContent;
        var btn = document.getElementById('copyBtn');

        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                btn.textContent = '✅ コピーしました！';
                btn.classList.add('copied');
            });
        } else {
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

    // フォーム送信処理
    document.getElementById('submitForm').addEventListener('submit', function(e) {
        // 高評価かつ Google アカウント未選択の場合はブロック
        if (rating >= 4 && !googleSelected) {
            e.preventDefault();
            document.getElementById('googleError').style.display = 'block';
            document.getElementById('googleSection').scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Googleアカウントありの場合：自動コピー＋Googleマップを別タブで開く
        if (rating >= 4 && document.getElementById('hasGoogleAccount').value === '1') {
            var text = document.getElementById('aiText').textContent;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
            } else {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
            var url = document.getElementById('googleReviewUrl').value;
            window.open(url, '_blank');
        }

        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        document.getElementById('submitText').style.display = 'none';
        document.getElementById('loadingSpinner').style.display = 'block';
    });

    // 初期化
    if (rating >= 4) {
        initGoogleClient();
    }
</script>
<style>
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
@endpush
