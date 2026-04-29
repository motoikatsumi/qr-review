@extends('layouts.admin')

@section('title', '返信カテゴリ・キーワード管理')

@push('styles')
<style>
    .category-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        margin-bottom: 0;
        overflow: hidden;
    }
    .category-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        background: #f8f9fa;
        border-bottom: 1px solid #e5e7eb;
        gap: 12px;
        flex-wrap: wrap;
    }
    .category-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 0;
    }
    .category-name {
        font-weight: 600;
        font-size: 0.95rem;
        color: #1e1b4b;
    }
    .category-actions {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-shrink: 0;
    }
    .kw-list {
        padding: 12px 16px;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .kw-chip {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 12px;
        background: #f0f4ff;
        border: 1px solid #e0e5f0;
        border-radius: 20px;
        font-size: 0.8rem;
        color: #333;
        cursor: default;
        transition: all 0.15s;
    }
    .kw-chip:hover {
        background: #e0e8ff;
        border-color: #667eea;
    }
    .kw-chip .kw-keyword-tip {
        display: none;
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%);
        background: #1e1b4b;
        color: #fff;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 0.72rem;
        white-space: nowrap;
        z-index: 10;
        pointer-events: none;
    }
    .kw-chip .kw-keyword-tip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: #1e1b4b;
    }
    .kw-chip:hover .kw-keyword-tip {
        display: block;
    }
    .kw-chip-inactive {
        opacity: 0.5;
        text-decoration: line-through;
    }
    .kw-chip-actions {
        display: none;
        position: absolute;
        top: -6px;
        right: -6px;
        align-items: center;
        gap: 2px;
        background: white;
        border: 1px solid #e0e5f0;
        border-radius: 10px;
        padding: 1px 4px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        z-index: 5;
    }
    .kw-chip:hover .kw-chip-actions {
        display: inline-flex;
    }
    .kw-chip-actions .kw-action-btn {
        background: none;
        border: none;
        padding: 0 2px;
        font-size: 0.75rem;
        cursor: pointer;
        color: #667eea;
        line-height: 1;
    }
    .kw-chip-actions .kw-action-btn.kw-delete-btn {
        color: #ef4444;
    }
    .empty-message {
        padding: 20px;
        text-align: center;
        color: #999;
        font-size: 0.85rem;
    }

    /* モーダル */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.4);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .modal-overlay.active {
        display: flex;
    }
    .modal {
        background: white;
        border-radius: 14px;
        width: 90%;
        max-width: 480px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        animation: modalIn 0.2s ease;
    }
    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .modal-header {
        padding: 18px 24px;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-close {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: #999;
        padding: 4px 8px;
    }
    .modal-body {
        padding: 20px 24px;
    }
    .modal-body .form-group {
        margin-bottom: 14px;
    }
    .modal-body .form-group label {
        display: block;
        font-size: 0.83rem;
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
    }
    .modal-body .form-group input,
    .modal-body .form-group select {
        width: 100%;
        padding: 9px 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.88rem;
        font-family: inherit;
        outline: none;
        transition: border-color 0.2s;
    }
    .modal-body .form-group input:focus,
    .modal-body .form-group select:focus {
        border-color: #667eea;
    }
    .modal-footer {
        padding: 14px 24px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }
    .status-toggle {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .status-toggle label {
        font-size: 0.83rem;
        color: #555;
        margin-bottom: 0 !important;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>💬 返信カテゴリ・キーワード管理</h1>
    <div style="display:flex;gap:12px;align-items:center;">
        @include('admin._partials.trash-filter', ['baseUrl' => '/admin/reply-categories'])
        @if(!$showTrashed)
        <div class="btn-group">
            <button class="btn btn-success" onclick="openAiModal()" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);">✨ AIで自動生成</button>
            <button class="btn btn-primary" onclick="openCategoryModal()">＋ カテゴリ追加</button>
            <button class="btn btn-info" onclick="openKeywordModal()">＋ キーワード追加</button>
        </div>
        @endif
    </div>
</div>

@if($showTrashed)
    @if($trashedCategories->isEmpty() && $trashedKeywords->isEmpty())
        <div class="card"><div class="empty-message" style="padding:40px;text-align:center;color:#888;">🗑 ゴミ箱は空です。</div></div>
    @else
        @if($trashedCategories->isNotEmpty())
        <h3 style="font-size:1rem;color:#92400e;margin:14px 0 10px;">削除済みカテゴリ（{{ $trashedCategories->count() }}件）</h3>
        @foreach($trashedCategories as $cat)
        <div class="category-card" style="opacity:0.85;background:#fef3c7;padding:12px 16px;border-radius:10px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <span class="category-name">{{ $cat->name }}</span>
                <span style="font-size:0.78rem;color:#92400e;margin-left:10px;">削除日: {{ $cat->deleted_at?->format('Y/n/j H:i') }}</span>
            </div>
            <div>
                <form method="POST" action="/admin/reply-categories/categories/{{ $cat->id }}/restore" style="display:inline;">
                    @csrf <button type="submit" class="btn btn-success btn-sm">↩ 復元</button>
                </form>
                <form method="POST" action="/admin/reply-categories/categories/{{ $cat->id }}/force-delete" style="display:inline;"
                      onsubmit="return confirm('「{{ $cat->name }}」を完全削除しますか？\n配下のキーワードも全て物理削除されます。');">
                    @csrf @method('DELETE') <button type="submit" class="btn btn-danger btn-sm">🗑 完全削除</button>
                </form>
            </div>
        </div>
        @endforeach
        @endif
        @if($trashedKeywords->isNotEmpty())
        <h3 style="font-size:1rem;color:#92400e;margin:14px 0 10px;">削除済みキーワード（{{ $trashedKeywords->count() }}件）</h3>
        <div class="card"><div class="card-body" style="padding:0;">
        <table style="width:100%;">
            <thead><tr><th>カテゴリ</th><th>キーワード</th><th>削除日</th><th>操作</th></tr></thead>
            <tbody>
                @foreach($trashedKeywords as $kw)
                <tr>
                    <td>{{ $kw->category->name ?? '(削除済)' }}</td>
                    <td>{{ $kw->label }} <span style="color:#888;font-size:0.78rem;">({{ $kw->keyword }})</span></td>
                    <td style="font-size:0.78rem;color:#92400e;">{{ $kw->deleted_at?->format('Y/n/j H:i') }}</td>
                    <td>
                        <form method="POST" action="/admin/reply-categories/keywords/{{ $kw->id }}/restore" style="display:inline;">
                            @csrf <button type="submit" class="btn btn-success btn-sm">↩ 復元</button>
                        </form>
                        <form method="POST" action="/admin/reply-categories/keywords/{{ $kw->id }}/force-delete" style="display:inline;"
                              onsubmit="return confirm('「{{ $kw->label }}」を完全削除しますか？');">
                            @csrf @method('DELETE') <button type="submit" class="btn btn-danger btn-sm">🗑 完全削除</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div></div>
        @endif
    @endif
@else

<p style="font-size:0.85rem; color:#666; margin-bottom:14px;">
    Google口コミへのAI返信で使用するカテゴリとキーワードを管理します。MEO対策として返信文に含めたいワードを設定してください。
</p>

{{-- 業種フィルタタブ --}}
@if($businessTypes->isNotEmpty())
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px;align-items:center;">
    <span style="font-size:0.78rem;color:#6b7280;margin-right:4px;">🏷️ 業種で絞り込み:</span>
    <a href="/admin/reply-categories"
       style="padding:5px 12px;border-radius:18px;text-decoration:none;font-size:0.8rem;font-weight:600;
              {{ $filterBt === '' ? 'background:#1e1b4b;color:white;' : 'background:#f3f4f6;color:#555;' }}">
        すべて
    </a>
    <a href="/admin/reply-categories?business_type=common"
       style="padding:5px 12px;border-radius:18px;text-decoration:none;font-size:0.8rem;font-weight:600;
              {{ $filterBt === 'common' ? 'background:#6366f1;color:white;' : 'background:#f3f4f6;color:#555;' }}">
        業種共通
    </a>
    @foreach($businessTypes as $bt)
        <a href="/admin/reply-categories?business_type={{ $bt->id }}"
           style="padding:5px 12px;border-radius:18px;text-decoration:none;font-size:0.8rem;font-weight:600;
                  {{ (string)$filterBt === (string)$bt->id ? 'background:#10b981;color:white;' : 'background:#f3f4f6;color:#555;' }}">
            {{ $bt->name }}
        </a>
    @endforeach
</div>
@endif

@if($categories->isEmpty())
    <div class="card">
        <div class="empty-message" style="padding:40px;text-align:center;">
            <p style="font-size:3rem;margin-bottom:12px;">💬</p>
            <p style="font-size:1.05rem;color:#374151;font-weight:600;margin-bottom:6px;">まだカテゴリがありません</p>
            <p style="font-size:0.85rem;color:#6b7280;margin-bottom:18px;">Google レビューへの返信で使うカテゴリを登録しましょう</p>
            <button class="btn btn-success" onclick="openAiModal()" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);padding:10px 20px;margin-right:8px;">✨ AI で自動生成</button>
            <button class="btn btn-primary" onclick="openCategoryModal()" style="padding:10px 20px;">＋ カテゴリを追加</button>
        </div>
    </div>
@endif

<div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;">
@foreach ($categories as $category)
<div class="category-card">
    <div class="category-header">
        <div class="category-header-left">
            <span class="category-name">{{ $category->name }}</span>
            @if($category->business_type_id)
                <span class="badge badge-blue" style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:10px;font-size:0.7rem;">{{ $category->businessType->name ?? '業種不明' }}</span>
            @else
                <span class="badge" style="background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:10px;font-size:0.7rem;">業種共通</span>
            @endif
            @if(!$category->is_active)
                <span class="badge badge-gray">非表示</span>
            @endif
            <span class="badge badge-green">{{ $category->keywords->count() }}件</span>
        </div>
        <div class="category-actions">
            <button class="btn btn-sm btn-secondary" onclick="openCategoryEditModal({{ $category->id }}, '{{ e($category->name) }}', {{ $category->business_type_id ?? 'null' }}, {{ $category->sort_order }}, {{ $category->is_active ? 'true' : 'false' }})">編集</button>
            <form method="POST" action="/admin/reply-categories/categories/{{ $category->id }}" style="display:inline;" onsubmit="return confirm('このカテゴリをゴミ箱に移動しますか？\n（後で復元できます）')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">🗑 削除</button>
            </form>
        </div>
    </div>

    <div class="kw-list">
        @forelse ($category->keywords as $kw)
        <div class="kw-chip {{ !$kw->is_active ? 'kw-chip-inactive' : '' }}">
            <span class="kw-keyword-tip">{{ $kw->keyword }}</span>
            {{ $kw->label }}
            <span class="kw-chip-actions">
                <button type="button" class="kw-action-btn" onclick="openKeywordEditModal({{ $kw->id }}, '{{ e($kw->label) }}', '{{ e($kw->keyword) }}', {{ $kw->sort_order }}, {{ $kw->is_active ? 'true' : 'false' }})" title="編集">✏️</button>
                <form method="POST" action="/admin/reply-categories/keywords/{{ $kw->id }}" style="display:inline;" onsubmit="return confirm('このキーワードをゴミ箱に移動しますか？\n（後で復元できます）')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="kw-action-btn kw-delete-btn" title="削除">✕</button>
                </form>
            </span>
        </div>
        @empty
        <div class="empty-message" style="width:100%;">キーワードがありません</div>
        @endforelse
    </div>
</div>
@endforeach
</div>
@endif {{-- /showTrashed branch --}}

{{-- AI 生成モーダル --}}
<div class="modal-overlay" id="aiModal">
    <div class="modal" style="max-width:720px;">
        <div class="modal-header">
            <span>✨ AI で返信カテゴリを自動生成</span>
            <button class="modal-close" onclick="closeModal('aiModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="ai_business_type">対象業種</label>
                <select id="ai_business_type">
                    <option value="">全業種共通（一般的なお店）</option>
                    @foreach ($businessTypes as $bt)
                    <option value="{{ $bt->id }}">{{ $bt->name }}</option>
                    @endforeach
                </select>
                <p style="font-size:0.72rem;color:#999;margin-top:3px;">業種を選ぶと、その業種に合わせたMEO対策キーワードが生成されます</p>
            </div>
            <div style="display:flex;gap:10px;align-items:center;margin-bottom:14px;">
                <button type="button" class="btn btn-primary" id="aiGenerateBtn" onclick="generateAiCategories()">🤖 候補を生成する</button>
                <span id="aiStatus" style="font-size:0.85rem;color:#667eea;"></span>
            </div>

            <div id="aiResults" style="display:none;">
                <p style="font-size:0.83rem;color:#555;margin-bottom:8px;">✅ 追加したいカテゴリ・キーワードにチェックを入れて「選択した内容を追加」ボタンを押してください。</p>
                <div id="aiResultsList" style="border:1px solid #e5e7eb;border-radius:8px;padding:12px;max-height:380px;overflow-y:auto;background:#fafbfc;"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('aiModal')">閉じる</button>
            <button type="button" class="btn btn-primary" id="aiApplyBtn" onclick="applyAiCategories()" style="display:none;">選択した内容を追加</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="categoryModal">
    <div class="modal">
        <div class="modal-header">
            カテゴリ追加
            <button class="modal-close" onclick="closeModal('categoryModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/reply-categories/categories">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>カテゴリ名</label>
                    <input type="text" name="name" required maxlength="100" placeholder="例: 貴金属">
                </div>
                <div class="form-group">
                    <label>業種</label>
                    <select name="business_type_id" id="cat_create_business_type">
                        <option value="">（業種共通）— 全業種で表示</option>
                        @foreach($businessTypes as $bt)
                            <option value="{{ $bt->id }}" {{ (string)$filterBt === (string)$bt->id ? 'selected' : '' }}>{{ $bt->name }}</option>
                        @endforeach
                    </select>
                    <p class="form-hint" style="font-size:0.74rem;color:#9ca3af;margin-top:4px;">特定業種のみで使う場合は選択。共通カテゴリは「業種共通」を選んでください。</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('categoryModal')">キャンセル</button>
                <button type="submit" class="btn btn-primary">追加</button>
            </div>
        </form>
    </div>
</div>

{{-- カテゴリ編集モーダル --}}
<div class="modal-overlay" id="categoryEditModal">
    <div class="modal">
        <div class="modal-header">
            カテゴリ編集
            <button class="modal-close" onclick="closeModal('categoryEditModal')">&times;</button>
        </div>
        <form method="POST" id="categoryEditForm">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label>カテゴリ名</label>
                    <input type="text" name="name" id="editCategoryName" required maxlength="100">
                </div>
                <div class="form-group">
                    <label>業種</label>
                    <select name="business_type_id" id="editCategoryBusinessType">
                        <option value="">（業種共通）— 全業種で表示</option>
                        @foreach($businessTypes as $bt)
                            <option value="{{ $bt->id }}">{{ $bt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>表示順</label>
                    <input type="number" name="sort_order" id="editCategorySortOrder" min="0">
                </div>
                <div class="status-toggle">
                    <input type="checkbox" name="is_active" value="1" id="editCategoryActive">
                    <label for="editCategoryActive">有効</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('categoryEditModal')">キャンセル</button>
                <button type="submit" class="btn btn-primary">更新</button>
            </div>
        </form>
    </div>
</div>

{{-- キーワード追加モーダル --}}
<div class="modal-overlay" id="keywordModal">
    <div class="modal">
        <div class="modal-header">
            キーワード追加
            <button class="modal-close" onclick="closeModal('keywordModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/reply-categories/keywords">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>カテゴリ</label>
                    <select name="category_id" required>
                        <option value="">選択してください</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>ラベル（表示名）</label>
                    <input type="text" name="label" required maxlength="255" placeholder="例: 金">
                </div>
                <div class="form-group">
                    <label>キーワード（AI生成プロンプトに渡す語句）</label>
                    <input type="text" name="keyword" required maxlength="255" placeholder="例: 金 買取 査定">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('keywordModal')">キャンセル</button>
                <button type="submit" class="btn btn-primary">追加</button>
            </div>
        </form>
    </div>
</div>

{{-- キーワード編集モーダル --}}
<div class="modal-overlay" id="keywordEditModal">
    <div class="modal">
        <div class="modal-header">
            キーワード編集
            <button class="modal-close" onclick="closeModal('keywordEditModal')">&times;</button>
        </div>
        <form method="POST" id="keywordEditForm">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label>ラベル</label>
                    <input type="text" name="label" id="editKwLabel" required maxlength="255">
                </div>
                <div class="form-group">
                    <label>キーワード</label>
                    <input type="text" name="keyword" id="editKwKeyword" required maxlength="255">
                </div>
                <div class="form-group">
                    <label>表示順</label>
                    <input type="number" name="sort_order" id="editKwSortOrder" min="0">
                </div>
                <div class="status-toggle">
                    <input type="checkbox" name="is_active" value="1" id="editKwActive">
                    <label for="editKwActive">有効</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('keywordEditModal')">キャンセル</button>
                <button type="submit" class="btn btn-primary">更新</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openAiModal() {
        document.getElementById('aiModal').classList.add('active');
        document.getElementById('aiResults').style.display = 'none';
        document.getElementById('aiResultsList').innerHTML = '';
        document.getElementById('aiStatus').textContent = '';
        document.getElementById('aiApplyBtn').style.display = 'none';
    }

    let aiData = null;

    async function generateAiCategories() {
        const btn = document.getElementById('aiGenerateBtn');
        const status = document.getElementById('aiStatus');
        const businessTypeId = document.getElementById('ai_business_type').value;
        btn.disabled = true;
        btn.textContent = '生成中…';
        status.textContent = '🤖 AI が考えています（10〜30 秒）…';
        document.getElementById('aiResults').style.display = 'none';
        document.getElementById('aiApplyBtn').style.display = 'none';

        try {
            const res = await fetch('/admin/reply-categories/ai-suggest', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ business_type_id: businessTypeId || null }),
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.error || '生成に失敗しました');

            aiData = json.data;
            renderAiResults(aiData.categories);
            document.getElementById('aiResults').style.display = 'block';
            document.getElementById('aiApplyBtn').style.display = 'inline-block';
            status.innerHTML = '<span style="color:#10b981;">✅ ' + aiData.categories.length + ' カテゴリを生成しました</span>';
        } catch (e) {
            status.innerHTML = '<span style="color:#ef4444;">❌ ' + e.message + '</span>';
        } finally {
            btn.disabled = false;
            btn.textContent = '🤖 候補を生成する';
        }
    }

    function renderAiResults(categories) {
        let html = '';
        categories.forEach(function(cat, ci) {
            html += '<div style="margin-bottom:14px;padding:10px;background:white;border:1px solid #e5e7eb;border-radius:8px;">';
            html += '<label style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:0.9rem;margin-bottom:8px;cursor:pointer;">';
            html += '<input type="checkbox" class="ai-cat-check" data-ci="' + ci + '" checked onchange="toggleCategoryKeywords(' + ci + ')">';
            html += '📁 ' + escapeHtml(cat.name);
            html += '</label>';
            html += '<div style="padding-left:24px;display:flex;flex-wrap:wrap;gap:6px;">';
            cat.keywords.forEach(function(k, ki) {
                html += '<label style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;background:#f0f4ff;border:1px solid #e0e5f0;border-radius:16px;font-size:0.8rem;cursor:pointer;">';
                html += '<input type="checkbox" class="ai-kw-check" data-ci="' + ci + '" data-ki="' + ki + '" checked>';
                html += escapeHtml(k.label);
                html += '<span style="color:#888;font-size:0.7rem;">(' + escapeHtml(k.keyword.substring(0, 25)) + (k.keyword.length > 25 ? '…' : '') + ')</span>';
                html += '</label>';
            });
            html += '</div></div>';
        });
        document.getElementById('aiResultsList').innerHTML = html;
    }

    function toggleCategoryKeywords(ci) {
        const catChecked = document.querySelector('.ai-cat-check[data-ci="' + ci + '"]').checked;
        document.querySelectorAll('.ai-kw-check[data-ci="' + ci + '"]').forEach(function(cb) {
            cb.disabled = !catChecked;
            if (!catChecked) cb.checked = false;
            else cb.checked = true;
        });
    }

    async function applyAiCategories() {
        if (!aiData) return;
        const selected = [];

        aiData.categories.forEach(function(cat, ci) {
            const catChecked = document.querySelector('.ai-cat-check[data-ci="' + ci + '"]').checked;
            if (!catChecked) return;
            const keywords = [];
            cat.keywords.forEach(function(k, ki) {
                const cb = document.querySelector('.ai-kw-check[data-ci="' + ci + '"][data-ki="' + ki + '"]');
                if (cb && cb.checked) keywords.push(k);
            });
            if (keywords.length === 0) return;
            selected.push({ name: cat.name, keywords: keywords });
        });

        if (selected.length === 0) {
            alert('追加するカテゴリ／キーワードを選択してください。');
            return;
        }

        const btn = document.getElementById('aiApplyBtn');
        btn.disabled = true;
        btn.textContent = '保存中…';
        try {
            const res = await fetch('/admin/reply-categories/ai-apply', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ categories: selected }),
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.error || '保存に失敗しました');
            alert(json.message || '保存しました。');
            location.reload();
        } catch (e) {
            alert('エラー: ' + e.message);
            btn.disabled = false;
            btn.textContent = '選択した内容を追加';
        }
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, function(c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
        });
    }

    function openCategoryModal() {
        document.getElementById('categoryModal').classList.add('active');
    }
    function openKeywordModal() {
        document.getElementById('keywordModal').classList.add('active');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function openCategoryEditModal(id, name, businessTypeId, sortOrder, isActive) {
        document.getElementById('categoryEditForm').action = '/admin/reply-categories/categories/' + id;
        document.getElementById('editCategoryName').value = name;
        document.getElementById('editCategoryBusinessType').value = businessTypeId === null || businessTypeId === undefined ? '' : String(businessTypeId);
        document.getElementById('editCategorySortOrder').value = sortOrder;
        document.getElementById('editCategoryActive').checked = isActive;
        document.getElementById('categoryEditModal').classList.add('active');
    }

    function openKeywordEditModal(id, label, keyword, sortOrder, isActive) {
        document.getElementById('keywordEditForm').action = '/admin/reply-categories/keywords/' + id;
        document.getElementById('editKwLabel').value = label;
        document.getElementById('editKwKeyword').value = keyword;
        document.getElementById('editKwSortOrder').value = sortOrder;
        document.getElementById('editKwActive').checked = isActive;
        document.getElementById('keywordEditModal').classList.add('active');
    }

    // モーダル外クリックで閉じる
    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });
</script>
@endpush
@endsection
