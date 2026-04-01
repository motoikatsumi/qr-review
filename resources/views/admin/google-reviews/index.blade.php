@extends('layouts.admin')
@section('title', 'Google口コミ管理')

@section('content')
<div class="page-header">
    <h1>🌐 Google口コミ管理</h1>
    <div class="btn-group">
        <form method="POST" action="/admin/google-reviews/sync" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-primary">🔄 口コミを同期</button>
        </form>
    </div>
</div>

{{-- 一括操作バー（未返信がある場合のみ表示） --}}
@php $unrepliedIds = $reviews->filter(fn($r) => !$r->reply_comment)->pluck('id')->toArray(); @endphp
@if(count($unrepliedIds) > 0)
<div class="card" style="margin-bottom: 20px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="card-body" style="padding: 14px 20px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <span style="color:white; font-weight:600; font-size:0.9rem;">⚡ 一括操作（このページの未返信 {{ count($unrepliedIds) }}件）</span>
        <button type="button" class="btn btn-sm" style="background:white; color:#667eea; font-weight:600;" onclick="bulkGenerateReplies()" id="bulk-generate-btn">
            🤖 一括返信作成
        </button>
        <button type="button" class="btn btn-sm" style="background:#10b981; color:white; font-weight:600; display:none;" onclick="bulkPostReplies()" id="bulk-post-btn">
            📤 一括返信投稿
        </button>
        <span id="bulk-progress" style="display:none; color:white; font-size:0.85rem;"></span>
    </div>
</div>
@endif

{{-- フィルター --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body" style="padding: 14px 20px;">
        <form method="GET" action="/admin/google-reviews" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <select name="store_id" style="padding:8px 12px; border:2px solid #e5e7eb; border-radius:8px; font-size:0.85rem;">
                <option value="">全店舗</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                @endforeach
            </select>
            <select name="rating" style="padding:8px 12px; border:2px solid #e5e7eb; border-radius:8px; font-size:0.85rem;">
                <option value="">全評価</option>
                @for($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ str_repeat('★', $i) }}{{ str_repeat('☆', 5-$i) }}</option>
                @endfor
            </select>
            <select name="reply_status" style="padding:8px 12px; border:2px solid #e5e7eb; border-radius:8px; font-size:0.85rem;">
                <option value="">全て</option>
                <option value="unreplied" {{ request('reply_status') === 'unreplied' ? 'selected' : '' }}>未返信</option>
                <option value="replied" {{ request('reply_status') === 'replied' ? 'selected' : '' }}>返信済み</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">絞り込み</button>
            @if(request()->hasAny(['store_id', 'rating', 'reply_status']))
                <a href="/admin/google-reviews" class="btn btn-secondary btn-sm">リセット</a>
            @endif
        </form>
    </div>
</div>

{{-- 口コミ一覧 --}}
@if($reviews->count() === 0)
    <div class="card">
        <div class="card-body" style="text-align:center; padding:40px; color:#999;">
            @if(request()->hasAny(['store_id', 'rating', 'reply_status']))
                条件に一致する口コミはありません。
            @else
                まだ口コミが同期されていません。「口コミを同期」ボタンで取得してください。
            @endif
        </div>
    </div>
@else
    @foreach($reviews as $review)
    <div class="card" style="margin-bottom: 16px;" id="review-{{ $review->id }}">
        <div class="card-body">
            {{-- ヘッダー部分 --}}
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    @if($review->reviewer_photo_url)
                        <img src="{{ $review->reviewer_photo_url }}" alt="" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                    @else
                        <div style="width:40px; height:40px; border-radius:50%; background:#e5e7eb; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">👤</div>
                    @endif
                    <div>
                        <div style="font-weight:600; font-size:0.9rem;">{{ $review->reviewer_name }}</div>
                        <div style="font-size:0.8rem; color:#999;">{{ $review->reviewed_at->format('Y/m/d H:i') }}</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="badge {{ $review->store ? 'badge-gray' : '' }}" style="font-size:0.75rem;">{{ $review->store->name ?? '不明' }}</span>
                    @if($review->reply_comment)
                        <span class="badge badge-green">返信済み</span>
                    @else
                        <span class="badge badge-red">未返信</span>
                    @endif
                </div>
            </div>

            {{-- 星評価 --}}
            <div class="stars" style="margin-bottom:8px;">
                @for($i = 1; $i <= 5; $i++)
                    {{ $i <= $review->rating ? '★' : '☆' }}
                @endfor
            </div>

            {{-- 口コミ本文 --}}
            @if($review->comment)
                <p style="font-size:0.9rem; line-height:1.7; margin-bottom:16px; color:#333;">{{ $review->comment }}</p>
            @else
                <p style="font-size:0.85rem; color:#999; margin-bottom:16px;">（テキストなし・星評価のみ）</p>
            @endif

            {{-- 既存の返信 --}}
            @if($review->reply_comment)
                {{-- 表示モード --}}
                <div id="reply-display-{{ $review->id }}">
                    <div style="background:#f0fdf4; border-left:4px solid #10b981; padding:12px 16px; border-radius:0 8px 8px 0; margin-bottom:12px;">
                        <div style="font-size:0.75rem; color:#065f46; font-weight:600; margin-bottom:4px;">💬 オーナーからの返信（{{ $review->replied_at?->format('Y/m/d H:i') }}）</div>
                        <p style="font-size:0.85rem; line-height:1.6; color:#333; margin:0; white-space:pre-line;">{{ $review->reply_comment }}</p>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEditReply({{ $review->id }})">✏️ 編集</button>
                        <form method="POST" action="/admin/google-reviews/{{ $review->id }}/reply" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="store_id" value="{{ request('store_id') }}">
                            <input type="hidden" name="rating" value="{{ request('rating') }}">
                            <input type="hidden" name="reply_status" value="{{ request('reply_status') }}">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('返信を削除しますか？')">返信を削除</button>
                        </form>
                    </div>
                </div>

                {{-- 編集モード --}}
                <div id="reply-edit-{{ $review->id }}" style="display:none;">
                    <div class="reply-form" style="background:#f8f9ff; border-radius:10px; padding:16px; margin-top:8px;">
                        <div style="font-size:0.85rem; font-weight:600; color:#1e1b4b; margin-bottom:12px;">✏️ 返信を編集</div>

                        {{-- 新規/リピーター/不明 選択 --}}
                        <div style="margin-bottom:12px;">
                            <label style="font-size:0.8rem; font-weight:600; color:#555; display:block; margin-bottom:6px;">顧客タイプ</label>
                            <div style="display:flex; gap:12px;">
                                <label style="display:inline-flex; align-items:center; gap:4px; cursor:pointer; font-size:0.85rem;">
                                    <input type="radio" name="customer_type_{{ $review->id }}" value="unknown" checked class="customer-type-{{ $review->id }}"> 不明
                                </label>
                                <label style="display:inline-flex; align-items:center; gap:4px; cursor:pointer; font-size:0.85rem;">
                                    <input type="radio" name="customer_type_{{ $review->id }}" value="new" class="customer-type-{{ $review->id }}"> 新規
                                </label>
                                <label style="display:inline-flex; align-items:center; gap:4px; cursor:pointer; font-size:0.85rem;">
                                    <input type="radio" name="customer_type_{{ $review->id }}" value="repeater" class="customer-type-{{ $review->id }}"> リピーター
                                </label>
                            </div>
                        </div>

                        {{-- カテゴリ・キーワード選択（タブ式） --}}
                        <div style="margin-bottom:12px;">
                            <label style="font-size:0.8rem; font-weight:600; color:#555; display:block; margin-bottom:6px;">カテゴリ & キーワード（MEO対策）</label>
                            <div class="cat-tabs" data-review="{{ $review->id }}" style="display:flex; flex-wrap:wrap; gap:4px; margin-bottom:8px;">
                                @foreach($categories as $cat)
                                    <button type="button" class="cat-tab cat-tab-{{ $review->id }}" data-review="{{ $review->id }}" data-cat="{{ $cat->id }}"
                                            style="padding:4px 10px; background:white; border:1px solid #e5e7eb; border-radius:6px; font-size:0.75rem; cursor:pointer; transition:all 0.2s; color:#555;">
                                        {{ $cat->name }}
                                        <span class="cat-count" style="display:none; color:#667eea; font-weight:700;"></span>
                                    </button>
                                @endforeach
                            </div>
                            @foreach($categories as $cat)
                                <div class="kw-panel kw-panel-{{ $review->id }}" data-cat="{{ $cat->id }}" style="display:none; flex-wrap:wrap; gap:5px; padding:8px 0;">
                                    @foreach($cat->keywords as $kw)
                                        <label style="display:inline-flex; align-items:center; gap:3px; padding:4px 10px; background:white; border:1px solid #e5e7eb; border-radius:20px; font-size:0.8rem; cursor:pointer; transition:all 0.2s;">
                                            <input type="checkbox" class="kw-checkbox-{{ $review->id }}"
                                                   data-category="{{ $cat->name }}" data-cat-id="{{ $cat->id }}"
                                                   data-keyword="{{ $kw->keyword }}"
                                                   style="display:none;">
                                            <span class="kw-label">{{ $kw->label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                            <div class="selected-kw-{{ $review->id }}" style="min-height:0;"></div>
                        </div>

                        <form method="POST" action="/admin/google-reviews/{{ $review->id }}/reply">
                            @csrf
                            <input type="hidden" name="store_id" value="{{ request('store_id') }}">
                            <input type="hidden" name="rating" value="{{ request('rating') }}">
                            <input type="hidden" name="reply_status" value="{{ request('reply_status') }}">
                            <textarea name="reply_comment" id="reply-text-{{ $review->id }}" rows="10"
                                      style="width:100%; padding:10px 14px; border:2px solid #e5e7eb; border-radius:8px; font-size:0.9rem; font-family:inherit; resize:vertical; outline:none;"
                                      required>{{ $review->reply_comment }}</textarea>
                            <div style="margin-top:8px; display:flex; gap:8px; align-items:center;">
                                <button type="button" class="btn btn-info btn-sm" onclick="generateReply({{ $review->id }})">
                                    🤖 AIで再生成
                                </button>
                                <span class="ai-loading" id="ai-loading-{{ $review->id }}" style="display:none; font-size:0.8rem; color:#667eea;">生成中...</span>
                                <button type="submit" class="btn btn-primary btn-sm">📤 更新を投稿</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEditReply({{ $review->id }})">キャンセル</button>
                            </div>
                        </form>
                    </div>
                </div>
            @else
                {{-- 返信フォーム --}}
                <div class="reply-form" id="reply-form-{{ $review->id }}" style="background:#f8f9ff; border-radius:10px; padding:16px; margin-top:8px;">
                    <div style="font-size:0.85rem; font-weight:600; color:#1e1b4b; margin-bottom:12px;">💬 返信を作成</div>

                    {{-- 新規/リピーター/不明 選択 --}}
                    <div style="margin-bottom:12px;">
                        <label style="font-size:0.8rem; font-weight:600; color:#555; display:block; margin-bottom:6px;">顧客タイプ</label>
                        <div style="display:flex; gap:12px;">
                            <label style="display:inline-flex; align-items:center; gap:4px; cursor:pointer; font-size:0.85rem;">
                                <input type="radio" name="customer_type_{{ $review->id }}" value="unknown" checked class="customer-type-{{ $review->id }}"> 不明
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:4px; cursor:pointer; font-size:0.85rem;">
                                <input type="radio" name="customer_type_{{ $review->id }}" value="new" class="customer-type-{{ $review->id }}"> 新規
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:4px; cursor:pointer; font-size:0.85rem;">
                                <input type="radio" name="customer_type_{{ $review->id }}" value="repeater" class="customer-type-{{ $review->id }}"> リピーター
                            </label>
                        </div>
                    </div>

                    {{-- カテゴリ・キーワード選択（タブ式） --}}
                    <div style="margin-bottom:12px;">
                        <label style="font-size:0.8rem; font-weight:600; color:#555; display:block; margin-bottom:6px;">カテゴリ & キーワード（MEO対策）</label>
                        {{-- カテゴリタブ --}}
                        <div class="cat-tabs" data-review="{{ $review->id }}" style="display:flex; flex-wrap:wrap; gap:4px; margin-bottom:8px;">
                            @foreach($categories as $cat)
                                <button type="button" class="cat-tab cat-tab-{{ $review->id }}" data-review="{{ $review->id }}" data-cat="{{ $cat->id }}"
                                        style="padding:4px 10px; background:white; border:1px solid #e5e7eb; border-radius:6px; font-size:0.75rem; cursor:pointer; transition:all 0.2s; color:#555;">
                                    {{ $cat->name }}
                                    <span class="cat-count" style="display:none; color:#667eea; font-weight:700;"></span>
                                </button>
                            @endforeach
                        </div>
                        {{-- キーワードパネル（カテゴリごと） --}}
                        @foreach($categories as $cat)
                            <div class="kw-panel kw-panel-{{ $review->id }}" data-cat="{{ $cat->id }}" style="display:none; flex-wrap:wrap; gap:5px; padding:8px 0;">
                                @foreach($cat->keywords as $kw)
                                    <label style="display:inline-flex; align-items:center; gap:3px; padding:4px 10px; background:white; border:1px solid #e5e7eb; border-radius:20px; font-size:0.8rem; cursor:pointer; transition:all 0.2s;">
                                        <input type="checkbox" class="kw-checkbox-{{ $review->id }}"
                                               data-category="{{ $cat->name }}" data-cat-id="{{ $cat->id }}"
                                               data-keyword="{{ $kw->keyword }}"
                                               style="display:none;">
                                        <span class="kw-label">{{ $kw->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                        {{-- 選択中のキーワード表示 --}}
                        <div class="selected-kw-{{ $review->id }}" style="min-height:0;"></div>
                    </div>

                    {{-- AI生成ボタン & 返信テキスト（横並び風にコンパクト化） --}}
                    <form method="POST" action="/admin/google-reviews/{{ $review->id }}/reply">
                        @csrf
                        <input type="hidden" name="store_id" value="{{ request('store_id') }}">
                        <input type="hidden" name="rating" value="{{ request('rating') }}">
                        <input type="hidden" name="reply_status" value="{{ request('reply_status') }}">
                        <textarea name="reply_comment" id="reply-text-{{ $review->id }}" rows="10"
                                  placeholder="返信文を入力してください..."
                                  style="width:100%; padding:10px 14px; border:2px solid #e5e7eb; border-radius:8px; font-size:0.9rem; font-family:inherit; resize:vertical; outline:none;"
                                  required></textarea>
                        <div style="margin-top:8px; display:flex; gap:8px; align-items:center;">
                            <button type="button" class="btn btn-info btn-sm" onclick="generateReply({{ $review->id }})">
                                🤖 AIで返信を生成
                            </button>
                            <span class="ai-loading" id="ai-loading-{{ $review->id }}" style="display:none; font-size:0.8rem; color:#667eea;">生成中...</span>
                            <button type="submit" class="btn btn-primary btn-sm">📤 返信を投稿</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
    @endforeach

    {{ $reviews->appends(request()->query())->links('pagination::bootstrap-4') }}
@endif

@push('styles')
<style>
    .kw-checkbox-checked {
        background: #eff6ff !important;
        border-color: #667eea !important;
        color: #667eea;
    }
    .cat-tab-active {
        background: #667eea !important;
        color: #fff !important;
        border-color: #667eea !important;
    }
    .selected-kw-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 6px;
    }
    .selected-kw-chip {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 8px;
        background: #eff6ff;
        border: 1px solid #667eea;
        border-radius: 12px;
        font-size: 0.7rem;
        color: #667eea;
    }
    .selected-kw-chip .chip-remove {
        cursor: pointer;
        font-weight: 700;
        margin-left: 2px;
    }
</style>
@endpush

@push('scripts')
<script>
    // カテゴリタブ切り替え
    document.querySelectorAll('.cat-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var reviewId = this.getAttribute('data-review');
            var catId = this.getAttribute('data-cat');

            // タブのアクティブ切替
            document.querySelectorAll('.cat-tab-' + reviewId).forEach(function(t) {
                t.classList.remove('cat-tab-active');
            });
            this.classList.add('cat-tab-active');

            // パネル表示切替
            document.querySelectorAll('.kw-panel-' + reviewId).forEach(function(p) {
                p.style.display = 'none';
            });
            var panel = document.querySelector('.kw-panel-' + reviewId + '[data-cat="' + catId + '"]');
            if (panel) panel.style.display = 'flex';
        });
    });

    // キーワードチップの選択トグル
    document.querySelectorAll('[class^="kw-checkbox-"]').forEach(function(cb) {
        cb.closest('label').addEventListener('click', function(e) {
            e.preventDefault();
            cb.checked = !cb.checked;
            this.classList.toggle('kw-checkbox-checked', cb.checked);

            var reviewId = cb.className.replace('kw-checkbox-', '');
            updateSelectedDisplay(reviewId);
            updateCatCounts(reviewId);
        });
    });

    // 選択中キーワードの表示更新
    function updateSelectedDisplay(reviewId) {
        var container = document.querySelector('.selected-kw-' + reviewId);
        var checked = document.querySelectorAll('.kw-checkbox-' + reviewId + ':checked');
        if (checked.length === 0) {
            container.innerHTML = '';
            return;
        }
        var html = '<div class="selected-kw-summary">';
        checked.forEach(function(cb) {
            var label = cb.closest('label').querySelector('.kw-label').textContent;
            var kw = cb.getAttribute('data-keyword');
            html += '<span class="selected-kw-chip">' + label + ' <span class="chip-remove" data-review="' + reviewId + '" data-keyword="' + kw + '">&times;</span></span>';
        });
        html += '</div>';
        container.innerHTML = html;

        // 削除ボタンのイベント
        container.querySelectorAll('.chip-remove').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var rid = this.getAttribute('data-review');
                var kwVal = this.getAttribute('data-keyword');
                var target = document.querySelector('.kw-checkbox-' + rid + '[data-keyword="' + kwVal + '"]');
                if (target) {
                    target.checked = false;
                    target.closest('label').classList.remove('kw-checkbox-checked');
                }
                updateSelectedDisplay(rid);
                updateCatCounts(rid);
            });
        });
    }

    // カテゴリタブに選択数バッジ表示
    function updateCatCounts(reviewId) {
        document.querySelectorAll('.cat-tab-' + reviewId).forEach(function(tab) {
            var catId = tab.getAttribute('data-cat');
            var count = document.querySelectorAll('.kw-checkbox-' + reviewId + '[data-cat-id="' + catId + '"]:checked').length;
            var badge = tab.querySelector('.cat-count');
            if (count > 0) {
                badge.textContent = ' (' + count + ')';
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        });
    }

    // 返信編集モード切り替え
    function toggleEditReply(reviewId) {
        var display = document.getElementById('reply-display-' + reviewId);
        var edit = document.getElementById('reply-edit-' + reviewId);
        if (edit.style.display === 'none') {
            display.style.display = 'none';
            edit.style.display = 'block';
        } else {
            display.style.display = 'block';
            edit.style.display = 'none';
        }
    }

    // AI返信生成
    function generateReply(reviewId) {
        var sel = getReviewSelections(reviewId);

        var loading = document.getElementById('ai-loading-' + reviewId);
        var textarea = document.getElementById('reply-text-' + reviewId);
        loading.style.display = 'inline';

        fetch('/admin/google-reviews/generate-reply', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                review_id: reviewId,
                category: sel.categories.join('、'),
                keywords: sel.keywords,
                customer_type: sel.customerType,
            })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            loading.style.display = 'none';
            if (data.reply) {
                textarea.value = data.reply;
            } else if (data.error) {
                alert(data.error);
            }
        })
        .catch(function(err) {
            loading.style.display = 'none';
            alert('通信エラーが発生しました。');
        });
    }

    // 指定レビューIDの選択済みカテゴリ・キーワード・顧客タイプを取得
    function getReviewSelections(reviewId) {
        var checkboxes = document.querySelectorAll('.kw-checkbox-' + reviewId + ':checked');
        var categories = [];
        var keywords = [];
        checkboxes.forEach(function(cb) {
            var cat = cb.getAttribute('data-category');
            if (categories.indexOf(cat) === -1) categories.push(cat);
            keywords.push(cb.getAttribute('data-keyword'));
        });

        var customerType = 'unknown';
        var typeRadios = document.querySelectorAll('.customer-type-' + reviewId);
        typeRadios.forEach(function(r) {
            if (r.checked) customerType = r.value;
        });

        return { categories: categories, keywords: keywords, customerType: customerType };
    }

    // =====================================
    // 一括返信作成
    // =====================================
    var bulkUnrepliedIds = @json($unrepliedIds ?? []);

    async function bulkGenerateReplies() {
        if (bulkUnrepliedIds.length === 0) return;
        if (!confirm('このページの未返信 ' + bulkUnrepliedIds.length + '件にAI返信を一括生成します。よろしいですか？')) return;

        var btn = document.getElementById('bulk-generate-btn');
        var progress = document.getElementById('bulk-progress');
        var postBtn = document.getElementById('bulk-post-btn');
        btn.disabled = true;
        btn.textContent = '⏳ 生成中...';
        progress.style.display = 'inline';

        var successCount = 0;
        var failCount = 0;

        for (var i = 0; i < bulkUnrepliedIds.length; i++) {
            var reviewId = bulkUnrepliedIds[i];
            progress.textContent = '(' + (i + 1) + '/' + bulkUnrepliedIds.length + ') 生成中...';

            // 各口コミの選択状態を取得（カテゴリ・キーワード・顧客タイプ）
            var sel = getReviewSelections(reviewId);

            try {
                var res = await fetch('/admin/google-reviews/generate-reply', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        review_id: reviewId,
                        category: sel.categories.join('、'),
                        keywords: sel.keywords,
                        customer_type: sel.customerType,
                    })
                });
                var data = await res.json();
                if (data.reply) {
                    var textarea = document.getElementById('reply-text-' + reviewId);
                    if (textarea) {
                        textarea.value = data.reply;
                        textarea.style.borderColor = '#10b981';
                    }
                    successCount++;
                } else {
                    failCount++;
                }
            } catch (e) {
                failCount++;
            }
        }

        btn.disabled = false;
        btn.textContent = '🤖 一括返信作成';
        progress.textContent = '✅ ' + successCount + '件生成完了' + (failCount > 0 ? '（' + failCount + '件失敗）' : '') + ' → 内容を確認して一括投稿してください';

        if (successCount > 0) {
            postBtn.style.display = 'inline-block';
        }
    }

    // =====================================
    // 一括返信投稿
    // =====================================
    async function bulkPostReplies() {
        // 返信テキストが入力されている未返信口コミだけ収集
        var postData = [];
        for (var i = 0; i < bulkUnrepliedIds.length; i++) {
            var reviewId = bulkUnrepliedIds[i];
            var textarea = document.getElementById('reply-text-' + reviewId);
            if (textarea && textarea.value.trim() !== '') {
                postData.push({ review_id: reviewId, reply_comment: textarea.value.trim() });
            }
        }

        if (postData.length === 0) {
            alert('投稿する返信がありません。先に「一括返信作成」で返信文を生成してください。');
            return;
        }

        if (!confirm(postData.length + '件の返信をGoogleに投稿します。よろしいですか？')) return;

        var btn = document.getElementById('bulk-post-btn');
        var progress = document.getElementById('bulk-progress');
        btn.disabled = true;
        btn.textContent = '⏳ 投稿中...';

        var successCount = 0;
        var failCount = 0;

        for (var i = 0; i < postData.length; i++) {
            progress.textContent = '(' + (i + 1) + '/' + postData.length + ') 投稿中...';

            try {
                var res = await fetch('/admin/google-reviews/' + postData[i].review_id + '/bulk-reply', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        reply_comment: postData[i].reply_comment,
                    })
                });
                var data = await res.json();
                if (data.success) {
                    successCount++;
                    // 成功した口コミのボーダーを更新
                    var textarea = document.getElementById('reply-text-' + postData[i].review_id);
                    if (textarea) textarea.style.borderColor = '#6366f1';
                } else {
                    failCount++;
                }
            } catch (e) {
                failCount++;
            }
        }

        btn.disabled = false;
        progress.textContent = '🎉 ' + successCount + '件投稿完了' + (failCount > 0 ? '（' + failCount + '件失敗）' : '');

        if (successCount > 0) {
            setTimeout(function() {
                location.reload();
            }, 1500);
        }
    }
</script>
@endpush
@endsection
