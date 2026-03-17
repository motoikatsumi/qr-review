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
    <div class="btn-group">
        <button class="btn btn-primary" onclick="openCategoryModal()">＋ カテゴリ追加</button>
        <button class="btn btn-info" onclick="openKeywordModal()">＋ キーワード追加</button>
    </div>
</div>

<p style="font-size:0.85rem; color:#666; margin-bottom:20px;">
    Google口コミへのAI返信で使用するカテゴリとキーワードを管理します。MEO対策として返信文に含めたいワードを設定してください。
</p>

@if($categories->isEmpty())
    <div class="card">
        <div class="empty-message">
            <p style="font-size:2rem;margin-bottom:10px;">💬</p>
            <p>まだカテゴリがありません。「カテゴリ追加」から作成してください。</p>
        </div>
    </div>
@endif

<div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;">
@foreach ($categories as $category)
<div class="category-card">
    <div class="category-header">
        <div class="category-header-left">
            <span class="category-name">{{ $category->name }}</span>
            @if(!$category->is_active)
                <span class="badge badge-gray">非表示</span>
            @endif
            <span class="badge badge-green">{{ $category->keywords->count() }}件</span>
        </div>
        <div class="category-actions">
            <button class="btn btn-sm btn-secondary" onclick="openCategoryEditModal({{ $category->id }}, '{{ e($category->name) }}', {{ $category->sort_order }}, {{ $category->is_active ? 'true' : 'false' }})">編集</button>
            <form method="POST" action="/admin/reply-categories/categories/{{ $category->id }}" style="display:inline;" onsubmit="return confirm('このカテゴリと全キーワードを削除します。よろしいですか？')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">削除</button>
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
                <form method="POST" action="/admin/reply-categories/keywords/{{ $kw->id }}" style="display:inline;" onsubmit="return confirm('このキーワードを削除しますか？')">
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
    function openCategoryModal() {
        document.getElementById('categoryModal').classList.add('active');
    }
    function openKeywordModal() {
        document.getElementById('keywordModal').classList.add('active');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function openCategoryEditModal(id, name, sortOrder, isActive) {
        document.getElementById('categoryEditForm').action = '/admin/reply-categories/categories/' + id;
        document.getElementById('editCategoryName').value = name;
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
