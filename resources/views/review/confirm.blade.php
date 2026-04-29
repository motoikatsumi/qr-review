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
    .photo-cta {
        margin-top: 10px;
        padding: 10px 14px;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 2px solid #f59e0b;
        border-radius: 10px;
        text-align: center;
    }
    .photo-cta p {
        margin: 0;
        font-size: 0.8rem;
        color: #78350f;
        line-height: 1.6;
        font-weight: 500;
    }
    .photo-cta .icon {
        font-size: 1.1rem;
        vertical-align: middle;
    }
    /* アップロード画像プレビュー */
    .confirm-images {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
        gap: 6px;
        margin-top: 6px;
    }
    .confirm-images .img-cell {
        position: relative;
        aspect-ratio: 1;
        border-radius: 8px;
        overflow: hidden;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
    }
    .confirm-images .img-cell img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
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
    .btn-save-photos {
        margin-top: 8px;
        width: 100%;
        padding: 10px 14px;
        background: white;
        color: #d97706;
        border: 2px solid #f59e0b;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .btn-save-photos:hover {
        background: #fffbeb;
        transform: translateY(-1px);
    }
    .btn-save-photos:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    .save-photos-hint {
        margin-top: 6px;
        font-size: 0.72rem;
        color: #92400e;
        text-align: center;
        line-height: 1.5;
    }
    /* 保存ガイド用モーダル */
    .save-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.85);
        z-index: 9999;
        overflow-y: auto;
        padding: 20px;
    }
    .save-modal.active { display: block; }
    .save-modal-inner {
        max-width: 480px;
        margin: 0 auto;
        background: white;
        border-radius: 12px;
        padding: 16px;
    }
    .save-modal h3 {
        margin: 0 0 8px;
        font-size: 1rem;
        color: #1f2937;
    }
    .save-modal-instruction {
        background: #fef3c7;
        border: 1px solid #fcd34d;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 0.82rem;
        color: #78350f;
        line-height: 1.6;
        margin-bottom: 12px;
    }
    .save-modal-images img {
        width: 100%;
        max-height: 60vh;
        object-fit: contain;
        border-radius: 8px;
        margin-bottom: 8px;
        background: #f3f4f6;
    }
    .save-modal-close {
        width: 100%;
        margin-top: 8px;
        padding: 10px;
        background: #1f2937;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
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

        @if (!empty($uploadedImageUrls ?? []))
        <div class="confirm-item">
            <p class="confirm-label">添付した写真（{{ count($uploadedImageUrls) }}枚）</p>
            <div class="confirm-images">
                @foreach ($uploadedImageUrls as $imgUrl)
                    <div class="img-cell"><img src="{{ $imgUrl }}" alt="" data-src="{{ $imgUrl }}"></div>
                @endforeach
            </div>
            <button type="button" class="btn-save-photos" id="savePhotosBtn">
                <span>📱</span><span>撮影した写真を端末に保存</span>
            </button>
            <p class="save-photos-hint">
                Googleマップでも添付するために、お使いの端末（iPhone/Android）に保存できます
            </p>
        </div>

        {{-- 保存方法ガイド（Web Share API 非対応端末向けフォールバック） --}}
        <div class="save-modal" id="saveModal" aria-hidden="true">
            <div class="save-modal-inner">
                <h3>📱 写真を端末に保存する方法</h3>
                <p class="save-modal-instruction">
                    下の写真を <strong>長押し</strong> → <strong>「写真に追加」</strong>（iPhone）<br>
                    または <strong>「画像をダウンロード」</strong>（Android）を選んでください
                </p>
                <div class="save-modal-images" id="saveModalImages"></div>
                <button type="button" class="save-modal-close" id="saveModalClose">閉じる</button>
            </div>
        </div>
        @endif

        {{-- 高評価の場合：口コミ文コピー＋Googleマップ誘導セクション --}}
        @php $threshold = $store->notify_threshold ?? 3; @endphp
        @if ($rating > $threshold)
        <div class="google-post-section">
            <div class="google-post-notice">
                <p>📍 下のボタンを押すと口コミ文が<strong>自動コピー</strong>され、<strong>Googleマップ</strong>が開きます</p>
                <p style="margin-top: 6px; font-size: 0.75rem; color: #000;"><strong>※ Googleアカウントがない場合は弊社システムに口コミが送信されます</strong></p>
                <p id="meoGuide" style="display:none; margin-top: 6px; font-size: 0.75rem; color: #1e3a5f;">店舗が表示されたら<strong>タップ</strong>→<strong>「クチコミ」</strong>→ 貼り付けてください</p>
            </div>
            @if (!empty($uploadedImageUrls ?? []))
            <div class="photo-cta">
                <p><span class="icon">📷</span> Googleマップ画面で<strong>写真も忘れずに添付</strong>してください！</p>
                <p style="margin-top: 4px; font-size: 0.72rem; color: #92400e; font-weight: 400;">写真付きの口コミは検索で表示されやすくなります🌟</p>
            </div>
            @endif
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
            {{-- 動的 persona（各質問グループの回答）--}}
            @foreach (($persona ?? []) as $pKey => $pVal)
                <input type="hidden" name="{{ $pKey }}" value="{{ $pVal }}">
            @endforeach
            {{-- アップロード済み画像（送信完了時にローカルから削除される） --}}
            @foreach (($uploadedImages ?? []) as $imgFilename)
                <input type="hidden" name="uploaded_images[]" value="{{ $imgFilename }}">
            @endforeach
            <button type="submit" class="btn btn-primary {{ $rating > $threshold ? 'btn-google-submit' : '' }}" id="submitBtn">
                <span id="submitText">{{ $rating > $threshold ? '📍 コピーしてGoogleマップへ →' : '送信する →' }}</span>
                <div class="loading-spinner" id="loadingSpinner" style="display:none;width:20px;height:20px;border:3px solid rgba(255,255,255,0.3);border-top-color:white;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto;"></div>
            </button>
        </form>
        @if ($rating > $threshold)
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
            @foreach (($persona ?? []) as $pKey => $pVal)
                <input type="hidden" name="{{ $pKey }}" value="{{ $pVal }}">
            @endforeach
            @foreach (($uploadedImages ?? []) as $imgFilename)
                <input type="hidden" name="uploaded_images[]" value="{{ $imgFilename }}">
            @endforeach
            <button type="submit" class="btn btn-back">✏️ 修正する</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    var rating = {{ $rating }};
    var threshold = {{ $threshold }};

    var thankyouUrl = '{{ url("/review/" . $store->slug . "/thankyou") }}';

    // ===== 写真を端末に保存 =====
    (function() {
        var btn = document.getElementById('savePhotosBtn');
        if (!btn) return;

        var modal = document.getElementById('saveModal');
        var modalImages = document.getElementById('saveModalImages');
        var modalClose = document.getElementById('saveModalClose');

        modalClose.addEventListener('click', function() { modal.classList.remove('active'); });
        modal.addEventListener('click', function(e) { if (e.target === modal) modal.classList.remove('active'); });

        async function urlsToFiles(urls) {
            var files = [];
            for (var i = 0; i < urls.length; i++) {
                try {
                    var res = await fetch(urls[i]);
                    var blob = await res.blob();
                    var ext = (blob.type.split('/')[1] || 'jpg').replace('jpeg', 'jpg');
                    var fname = 'photo-' + (i + 1) + '.' + ext;
                    files.push(new File([blob], fname, { type: blob.type }));
                } catch (e) { /* skip */ }
            }
            return files;
        }

        function showFallbackModal(urls) {
            modalImages.innerHTML = '';
            urls.forEach(function(u) {
                var img = document.createElement('img');
                img.src = u;
                modalImages.appendChild(img);
            });
            modal.classList.add('active');
        }

        btn.addEventListener('click', async function() {
            var urls = Array.from(document.querySelectorAll('.confirm-images img')).map(function(i) { return i.dataset.src || i.src; });
            if (!urls.length) return;

            btn.disabled = true;
            var originalText = btn.innerHTML;
            btn.innerHTML = '<span>📱</span><span>準備中...</span>';

            try {
                // Web Share API (HTTPS環境のiOS/Androidで「写真に保存」が選べる)
                if (navigator.canShare) {
                    var files = await urlsToFiles(urls);
                    if (files.length && navigator.canShare({ files: files })) {
                        try {
                            await navigator.share({ files: files, title: '口コミ用の写真' });
                            return;
                        } catch (e) {
                            if (e.name === 'AbortError') return; // ユーザーキャンセル
                        }
                    }
                }
                // フォールバック: 長押し保存ガイドを表示
                showFallbackModal(urls);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    })();

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
        if (rating > threshold) {
            e.preventDefault();

            // MEO検索経由かどうかを先に決定
            var meoData = document.getElementById('meoKeywords').value;
            var meoKeywords = meoData ? meoData.split(',').map(function(k) { return k.trim(); }).filter(Boolean) : [];
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
