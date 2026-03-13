@extends('layouts.admin')

@section('title', '口コミテーマ管理')

@push('styles')
<style>
    .category-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        margin-bottom: 20px;
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
    .theme-list {
        padding: 16px 20px;
    }
    .theme-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 8px;
        margin-bottom: 6px;
        transition: background 0.15s;
        flex-wrap: wrap;
    }
    .theme-item:hover {
        background: #f8f9ff;
    }
    .theme-icon {
        font-size: 1.3rem;
        width: 32px;
        text-align: center;
        flex-shrink: 0;
    }
    .theme-info {
        flex: 1;
        min-width: 150px;
    }
    .theme-label {
        font-weight: 600;
        font-size: 0.9rem;
        color: #333;
    }
    .theme-keyword {
        font-size: 0.78rem;
        color: #888;
        margin-top: 2px;
        word-break: break-all;
    }
    .theme-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
    .theme-actions {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
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
    .modal-body .form-group select,
    .modal-body .form-group textarea {
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
    .modal-body .form-group select:focus,
    .modal-body .form-group textarea:focus {
        border-color: #667eea;
    }
    .modal-footer {
        padding: 14px 24px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }
    .icon-preview {
        font-size: 2rem;
        display: inline-block;
        margin-left: 8px;
        vertical-align: middle;
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
    <h1>🏷️ 口コミテーマ管理</h1>
    <div class="btn-group">
        <button class="btn btn-primary" onclick="openCategoryModal()">＋ カテゴリ追加</button>
        <button class="btn btn-info" onclick="openThemeModal()">＋ テーマ追加</button>
    </div>
</div>

@if($categories->isEmpty())
    <div class="card">
        <div class="empty-message">
            <p style="font-size:2rem;margin-bottom:10px;">🏷️</p>
            <p>まだカテゴリがありません。「カテゴリ追加」から作成してください。</p>
        </div>
    </div>
@endif

@foreach ($categories as $category)
<div class="category-card">
    <div class="category-header">
        <div class="category-header-left">
            <span class="category-name">{{ $category->name }}</span>
            @if(!$category->is_active)
                <span class="badge badge-gray">非表示</span>
            @endif
            <span class="badge badge-green">{{ $category->themes->count() }}件</span>
        </div>
        <div class="category-actions">
            <button class="btn btn-sm btn-secondary" onclick="openCategoryEditModal({{ $category->id }}, '{{ e($category->name) }}', {{ $category->sort_order }}, {{ $category->is_active ? 'true' : 'false' }})">編集</button>
            <form method="POST" action="/admin/suggestion-themes/categories/{{ $category->id }}" style="display:inline;" onsubmit="return confirm('このカテゴリと全テーマを削除します。よろしいですか？')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">削除</button>
            </form>
        </div>
    </div>

    <div class="theme-list">
        @forelse ($category->themes as $theme)
        <div class="theme-item">
            <span class="theme-icon">{{ $theme->icon }}</span>
            <div class="theme-info">
                <div class="theme-label">
                    {{ $theme->label }}
                    @if(!$theme->is_active)
                        <span class="badge badge-gray">非表示</span>
                    @endif
                </div>
                <div class="theme-keyword">キーワード: {{ $theme->keyword }}</div>
            </div>
            <div class="theme-meta">
                <span style="font-size:0.75rem;color:#aaa;">順序: {{ $theme->sort_order }}</span>
            </div>
            <div class="theme-actions">
                <button class="btn btn-sm btn-secondary" onclick='openThemeEditModal(@json($theme))'>編集</button>
                <form method="POST" action="/admin/suggestion-themes/themes/{{ $theme->id }}" style="display:inline;" onsubmit="return confirm('このテーマを削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">削除</button>
                </form>
            </div>
        </div>
        @empty
        <div class="empty-message">テーマがありません。「テーマ追加」から作成してください。</div>
        @endforelse
    </div>
</div>
@endforeach

{{-- カテゴリ追加モーダル --}}
<div class="modal-overlay" id="categoryModal">
    <div class="modal">
        <div class="modal-header">
            <span>カテゴリ追加</span>
            <button class="modal-close" onclick="closeModal('categoryModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/suggestion-themes/categories">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="cat_name">カテゴリ名 <span style="color:#ef4444">*</span></label>
                    <input type="text" name="name" id="cat_name" required placeholder="例: 価格・金額">
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
            <span>カテゴリ編集</span>
            <button class="modal-close" onclick="closeModal('categoryEditModal')">&times;</button>
        </div>
        <form method="POST" id="categoryEditForm">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label for="cat_edit_name">カテゴリ名 <span style="color:#ef4444">*</span></label>
                    <input type="text" name="name" id="cat_edit_name" required>
                </div>
                <div class="form-group">
                    <label for="cat_edit_order">表示順</label>
                    <input type="number" name="sort_order" id="cat_edit_order" min="0" required>
                </div>
                <div class="form-group">
                    <div class="status-toggle">
                        <input type="checkbox" name="is_active" id="cat_edit_active" value="1">
                        <label for="cat_edit_active">有効（顧客フォームに表示）</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('categoryEditModal')">キャンセル</button>
                <button type="submit" class="btn btn-primary">更新</button>
            </div>
        </form>
    </div>
</div>

{{-- テーマ追加モーダル --}}
<div class="modal-overlay" id="themeModal">
    <div class="modal">
        <div class="modal-header">
            <span>テーマ追加</span>
            <button class="modal-close" onclick="closeModal('themeModal')">&times;</button>
        </div>
        <form method="POST" action="/admin/suggestion-themes/themes">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="theme_category">カテゴリ <span style="color:#ef4444">*</span></label>
                    <select name="category_id" id="theme_category" required>
                        <option value="">選択してください</option>
                        @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="theme_icon">アイコン（絵文字） <span style="color:#ef4444">*</span></label>
                    <input type="text" name="icon" id="theme_icon" required placeholder="例: 💰" maxlength="10">
                    <span class="icon-preview" id="iconPreview"></span>
                </div>
                <div class="form-group">
                    <label for="theme_label">ボタン表示名 <span style="color:#ef4444">*</span></label>
                    <input type="text" name="label" id="theme_label" required placeholder="例: 高価買取">
                </div>
                <div class="form-group">
                    <label for="theme_keyword">AIキーワード <span style="color:#ef4444">*</span></label>
                    <textarea name="keyword" id="theme_keyword" rows="2" required placeholder="例: 査定価格が高くて満足、高価買取"></textarea>
                    <p style="font-size:0.72rem;color:#999;margin-top:3px;">AI生成時にプロンプトへ渡されるキーワード</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('themeModal')">キャンセル</button>
                <button type="submit" class="btn btn-primary">追加</button>
            </div>
        </form>
    </div>
</div>

{{-- テーマ編集モーダル --}}
<div class="modal-overlay" id="themeEditModal">
    <div class="modal">
        <div class="modal-header">
            <span>テーマ編集</span>
            <button class="modal-close" onclick="closeModal('themeEditModal')">&times;</button>
        </div>
        <form method="POST" id="themeEditForm">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label for="theme_edit_category">カテゴリ <span style="color:#ef4444">*</span></label>
                    <select name="category_id" id="theme_edit_category" required>
                        @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="theme_edit_icon">アイコン（絵文字） <span style="color:#ef4444">*</span></label>
                    <input type="text" name="icon" id="theme_edit_icon" required maxlength="10">
                    <span class="icon-preview" id="editIconPreview"></span>
                </div>
                <div class="form-group">
                    <label for="theme_edit_label">ボタン表示名 <span style="color:#ef4444">*</span></label>
                    <input type="text" name="label" id="theme_edit_label" required>
                </div>
                <div class="form-group">
                    <label for="theme_edit_keyword">AIキーワード <span style="color:#ef4444">*</span></label>
                    <textarea name="keyword" id="theme_edit_keyword" rows="2" required></textarea>
                </div>
                <div class="form-group">
                    <label for="theme_edit_order">表示順</label>
                    <input type="number" name="sort_order" id="theme_edit_order" min="0" required>
                </div>
                <div class="form-group">
                    <div class="status-toggle">
                        <input type="checkbox" name="is_active" id="theme_edit_active" value="1">
                        <label for="theme_edit_active">有効（顧客フォームに表示）</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('themeEditModal')">キャンセル</button>
                <button type="submit" class="btn btn-primary">更新</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openCategoryModal() {
        document.getElementById('categoryModal').classList.add('active');
    }

    function openCategoryEditModal(id, name, sortOrder, isActive) {
        document.getElementById('categoryEditForm').action = '/admin/suggestion-themes/categories/' + id;
        document.getElementById('cat_edit_name').value = name;
        document.getElementById('cat_edit_order').value = sortOrder;
        document.getElementById('cat_edit_active').checked = isActive;
        document.getElementById('categoryEditModal').classList.add('active');
    }

    function openThemeModal() {
        document.getElementById('themeModal').classList.add('active');
    }

    function openThemeEditModal(theme) {
        document.getElementById('themeEditForm').action = '/admin/suggestion-themes/themes/' + theme.id;
        document.getElementById('theme_edit_category').value = theme.category_id;
        document.getElementById('theme_edit_icon').value = theme.icon;
        document.getElementById('editIconPreview').textContent = theme.icon;
        document.getElementById('theme_edit_label').value = theme.label;
        document.getElementById('theme_edit_keyword').value = theme.keyword;
        document.getElementById('theme_edit_order').value = theme.sort_order;
        document.getElementById('theme_edit_active').checked = theme.is_active;
        document.getElementById('themeEditModal').classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    // モーダル外クリックで閉じる
    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });

    // アイコンプレビュー
    document.getElementById('theme_icon').addEventListener('input', function() {
        document.getElementById('iconPreview').textContent = this.value;
    });
    document.getElementById('theme_edit_icon').addEventListener('input', function() {
        document.getElementById('editIconPreview').textContent = this.value;
    });
</script>
@endpush
