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

    /* Google投稿セクション */
    .google-post-section {
        margin-top: 16px;
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

        {{-- 高評価の場合：口コミ文コピー＋Googleマップ誘導セクション --}}
        @if ($rating >= 4)
        <div class="google-post-section">
            <div class="google-post-notice">
                <p>📍 下のボタンを押すと口コミ文が<strong>自動コピー</strong>され、<strong>Googleマップ</strong>が開きます</p>
                <p style="margin-top: 6px; font-size: 0.75rem; color: #000;"><strong>※ Googleアカウントがない場合は弊社システムに口コミが送信されます</strong></p>
                <p id="meoGuide" style="display:none; margin-top: 6px; font-size: 0.75rem; color: #1e3a5f;">店舗が表示されたら<strong>タップ</strong>→<strong>「クチコミ」</strong>→ 貼り付けてください</p>
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
            <input type="hidden" name="gender" value="{{ $gender }}">
            <input type="hidden" name="age" value="{{ $age }}">
            <button type="submit" class="btn btn-primary {{ $rating >= 4 ? 'btn-google-submit' : '' }}" id="submitBtn">
                <span id="submitText">{{ $rating >= 4 ? '📍 コピーしてGoogleマップへ →' : '送信する →' }}</span>
                <div class="loading-spinner" id="loadingSpinner" style="display:none;width:20px;height:20px;border:3px solid rgba(255,255,255,0.3);border-top-color:white;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto;"></div>
            </button>
        </form>
        @if ($rating >= 4)
        <input type="hidden" id="googleReviewUrl" value="{{ $store->google_review_url }}">
        <input type="hidden" id="storeName" value="{{ $store->name }}">
        <input type="hidden" id="meoKeywords" value="{{ $store->meo_keywords }}">
        <input type="hidden" id="meoRatio" value="{{ $store->meo_ratio }}">
        <input type="hidden" id="ludocid" value="{{ $store->ludocid }}">
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
<script>
    var rating = {{ $rating }};

    var thankyouUrl = '{{ url("/review/" . $store->slug . "/thankyou") }}';

    // Googleマップから戻った時にthankyouページへリダイレクト
    window.addEventListener('pageshow', function(event) {
        if (sessionStorage.getItem('review_submitted')) {
            sessionStorage.removeItem('review_submitted');
            window.location.replace(thankyouUrl);
        }
    });

    // フォーム送信処理
    document.getElementById('submitForm').addEventListener('submit', function(e) {
        var btn = document.getElementById('submitBtn');

        // 高評価の場合：fetchでバックグラウンド送信し、現在のタブでGoogleマップへ遷移
        if (rating >= 4) {
            e.preventDefault();

            // MEO検索経由かどうかを先に決定
            var meoData = document.getElementById('meoKeywords').value;
            var meoKeywords = meoData ? meoData.split(',').map(function(k) { return k.trim(); }).filter(Boolean) : ['買取', '質屋', '査定', '鑑定', '宝石', 'ブランド'];
            var shuffled = meoKeywords.sort(function() { return 0.5 - Math.random(); });
            var picked = shuffled.slice(0, 2);
            var meoRatioRaw = parseInt(document.getElementById('meoRatio').value);
            var meoRatio = isNaN(meoRatioRaw) ? 30 : meoRatioRaw;
            var useMeoSearch = Math.random() < (meoRatio / 100);

            // MEO検索経由の場合は案内テキストを表示
            if (useMeoSearch) {
                var guide = document.getElementById('meoGuide');
                if (guide) guide.style.display = 'block';
            }

            // コピー処理
            var text = document.querySelector('input[name="comment"]').value;
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

            btn.disabled = true;
            document.getElementById('submitText').style.display = 'none';
            document.getElementById('loadingSpinner').style.display = 'block';

            // fetchでフォームデータをバックグラウンド送信
            var form = document.getElementById('submitForm');
            var formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).finally(function() {
                // 戻った時にthankyouへリダイレクトするためフラグをセット
                sessionStorage.setItem('review_submitted', '1');

                // 送信完了後、現在のタブでGoogleマップへ遷移
                var baseUrl = document.getElementById('googleReviewUrl').value;
                var storeName = document.getElementById('storeName').value;

                var url;
                if (useMeoSearch) {
                    // Google検索経由（Web検索シグナル→MEO効果最大）
                    var searchQuery = storeName + ' ' + picked.join(' ');
                    var ludocid = document.getElementById('ludocid').value;
                    url = 'https://www.google.com/search?q=' + encodeURIComponent(searchQuery);
                    if (ludocid) {
                        url += '&ludocid=' + encodeURIComponent(ludocid);
                    }
                } else {
                    url = baseUrl;
                }

                window.location.href = url;
            });

            return;
        }

        // 低評価の場合：通常送信
        btn.disabled = true;
        document.getElementById('submitText').style.display = 'none';
        document.getElementById('loadingSpinner').style.display = 'block';
    });


</script>
<style>
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
@endpush
