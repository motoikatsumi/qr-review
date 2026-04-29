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

    /* 絵文字ピッカー */
    .emoji-picker-wrap {
        position: relative;
    }
    .emoji-picker-wrap .form-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .emoji-picker-wrap input {
        flex: 1;
    }
    .emoji-picker-btn {
        background: #f3f4f6;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 7px 12px;
        font-size: 1.1rem;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s;
        flex-shrink: 0;
        line-height: 1;
    }
    .emoji-picker-btn:hover {
        background: #e5e7eb;
        border-color: #667eea;
    }
    .emoji-panel {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 100;
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        margin-top: 4px;
        max-height: 260px;
        overflow-y: auto;
    }
    .emoji-panel.active {
        display: block;
    }
    .emoji-panel-section {
        padding: 6px 10px 2px;
    }
    .emoji-panel-section-title {
        font-size: 0.7rem;
        font-weight: 700;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 4px;
    }
    .emoji-panel-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(36px, 1fr));
        gap: 2px;
    }
    .emoji-panel-grid button {
        background: none;
        border: none;
        font-size: 1.3rem;
        padding: 4px;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.1s;
        line-height: 1.2;
    }
    .emoji-panel-grid button:hover {
        background: #f0f0ff;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>🏷️ 口コミテーマ管理</h1>
    <div style="display:flex;gap:12px;align-items:center;">
        @include('admin._partials.trash-filter', ['baseUrl' => '/admin/suggestion-themes'])
        @if(!$showTrashed)
        <div class="btn-group">
            <button class="btn btn-success" onclick="openAiModal()" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);">✨ AIで自動生成</button>
            <button class="btn btn-primary" onclick="openCategoryModal()">＋ カテゴリ追加</button>
            <button class="btn btn-info" onclick="openThemeModal()">＋ テーマ追加</button>
        </div>
        @endif
    </div>
</div>

@if($showTrashed)
    {{-- ゴミ箱表示 --}}
    @if($trashedCategories->isEmpty() && $trashedThemes->isEmpty())
        <div class="card"><div class="empty-message" style="padding:40px;text-align:center;color:#888;">
            🗑 ゴミ箱は空です。
        </div></div>
    @else
        @if($trashedCategories->isNotEmpty())
        <h3 style="font-size:1rem;color:#92400e;margin:14px 0 10px;">削除済みカテゴリ（{{ $trashedCategories->count() }}件）</h3>
        @foreach($trashedCategories as $cat)
        <div class="category-card" style="opacity:0.85;">
            <div class="category-header" style="background:#fef3c7;">
                <div class="category-header-left">
                    <span class="category-name">{{ $cat->name }}</span>
                    @if($cat->businessType)<span class="badge badge-blue">{{ $cat->businessType->name }}</span>@endif
                    <span style="font-size:0.78rem;color:#92400e;">削除日: {{ $cat->deleted_at?->format('Y/n/j H:i') }}</span>
                </div>
                <div class="category-actions">
                    <form method="POST" action="/admin/suggestion-themes/categories/{{ $cat->id }}/restore" style="display:inline;">
                        @csrf <button type="submit" class="btn btn-success btn-sm">↩ 復元</button>
                    </form>
                    <form method="POST" action="/admin/suggestion-themes/categories/{{ $cat->id }}/force-delete" style="display:inline;"
                          onsubmit="return confirm('「{{ $cat->name }}」を完全削除しますか？\n配下のテーマも全て物理削除されます。');">
                        @csrf @method('DELETE') <button type="submit" class="btn btn-danger btn-sm">🗑 完全削除</button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
        @endif

        @if($trashedThemes->isNotEmpty())
        <h3 style="font-size:1rem;color:#92400e;margin:14px 0 10px;">削除済みテーマ（{{ $trashedThemes->count() }}件）</h3>
        <div class="card"><div class="card-body" style="padding:0;">
        <table style="width:100%;">
            <thead><tr><th>カテゴリ</th><th>テーマ</th><th>削除日</th><th>操作</th></tr></thead>
            <tbody>
                @foreach($trashedThemes as $theme)
                <tr>
                    <td>{{ $theme->category->name ?? '(削除済)' }}</td>
                    <td>{{ $theme->icon }} {{ $theme->label }}</td>
                    <td style="font-size:0.78rem;color:#92400e;">{{ $theme->deleted_at?->format('Y/n/j H:i') }}</td>
                    <td>
                        <form method="POST" action="/admin/suggestion-themes/themes/{{ $theme->id }}/restore" style="display:inline;">
                            @csrf <button type="submit" class="btn btn-success btn-sm">↩ 復元</button>
                        </form>
                        <form method="POST" action="/admin/suggestion-themes/themes/{{ $theme->id }}/force-delete" style="display:inline;"
                              onsubmit="return confirm('「{{ $theme->label }}」を完全削除しますか？');">
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

<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <form method="POST" action="/admin/suggestion-themes/display-count" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            @csrf
            @method('PUT')
            <label style="font-size:0.9rem;font-weight:600;color:#555;white-space:nowrap;">📱 フォームに表示するテーマ数</label>
            <input type="number" name="display_count" value="{{ $displayCount }}" min="1" max="50" style="width:80px;padding:8px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.9rem;outline:none;text-align:center;">
            <button type="submit" class="btn btn-primary btn-sm">保存</button>
            <span style="font-size:0.75rem;color:#999;">※ 各カテゴリから最低1つ選出され、残り枠はランダムで表示されます</span>
        </form>
    </div>
</div>

@if($categories->isEmpty())
    <div class="card">
        <div class="empty-message" style="padding:40px;text-align:center;">
            <p style="font-size:3rem;margin-bottom:12px;">🏷️</p>
            <p style="font-size:1.05rem;color:#374151;font-weight:600;margin-bottom:6px;">まだカテゴリがありません</p>
            <p style="font-size:0.85rem;color:#6b7280;margin-bottom:18px;">QR レビューフォームでお客様に表示するテーマを登録しましょう</p>
            <button class="btn btn-success" onclick="openAiModal()" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);padding:10px 20px;margin-right:8px;">✨ AI で自動生成</button>
            <button class="btn btn-primary" onclick="openCategoryModal()" style="padding:10px 20px;">＋ カテゴリを追加</button>
        </div>
    </div>
@endif

@if(!$showTrashed)
@foreach ($categories as $category)
<div class="category-card">
    <div class="category-header">
        <div class="category-header-left">
            <span class="category-name">{{ $category->name }}</span>
            @if($category->business_type_id)
                <span class="badge badge-blue">{{ $category->businessType->name ?? '業種不明' }}</span>
            @else
                <span class="badge" style="background:#f3f4f6;color:#666;">全業種共通</span>
            @endif
            @if(!$category->is_active)
                <span class="badge badge-gray">非表示</span>
            @endif
            <span class="badge badge-green">{{ $category->themes->count() }}件</span>
        </div>
        <div class="category-actions">
            <button class="btn btn-sm btn-secondary" onclick="openCategoryEditModal({{ $category->id }}, '{{ e($category->name) }}', {{ $category->business_type_id ?? 'null' }}, {{ $category->sort_order }}, {{ $category->is_active ? 'true' : 'false' }})">編集</button>
            <form method="POST" action="/admin/suggestion-themes/categories/{{ $category->id }}" style="display:inline;" onsubmit="return confirm('このカテゴリと全テーマを削除します。\nこの操作は元に戻せません。よろしいですか？')">
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
                <form method="POST" action="/admin/suggestion-themes/themes/{{ $theme->id }}" style="display:inline;" onsubmit="return confirm('このテーマを削除しますか？\nこの操作は元に戻せません。')">
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
@endif {{-- /!showTrashed --}}
@endif {{-- /showTrashed branch --}}

{{-- AI 生成モーダル --}}
<div class="modal-overlay" id="aiModal">
    <div class="modal" style="max-width:720px;">
        <div class="modal-header">
            <span>✨ AI で口コミテーマを自動生成</span>
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
                <p style="font-size:0.72rem;color:#999;margin-top:3px;">業種を選ぶと、その業種に合わせたテーマが生成されます</p>
            </div>
            <div style="display:flex;gap:10px;align-items:center;margin-bottom:14px;">
                <button type="button" class="btn btn-primary" id="aiGenerateBtn" onclick="generateAiThemes()">🤖 候補を生成する</button>
                <span id="aiStatus" style="font-size:0.85rem;color:#667eea;"></span>
            </div>

            <div id="aiResults" style="display:none;">
                <p style="font-size:0.83rem;color:#555;margin-bottom:8px;">✅ 追加したいカテゴリ・テーマにチェックを入れて「選択した内容を追加」ボタンを押してください。</p>
                <div id="aiResultsList" style="border:1px solid #e5e7eb;border-radius:8px;padding:12px;max-height:380px;overflow-y:auto;background:#fafbfc;"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('aiModal')">閉じる</button>
            <button type="button" class="btn btn-primary" id="aiApplyBtn" onclick="applyAiThemes()" style="display:none;">選択した内容を追加</button>
        </div>
    </div>
</div>

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
                <div class="form-group">
                    <label for="cat_business_type">対象業種</label>
                    <select name="business_type_id" id="cat_business_type">
                        <option value="">全業種共通</option>
                        @foreach ($businessTypes as $bt)
                        <option value="{{ $bt->id }}">{{ $bt->name }}</option>
                        @endforeach
                    </select>
                    <p style="font-size:0.72rem;color:#999;margin-top:3px;">未選択の場合、すべての業種の口コミフォームに表示されます</p>
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
                    <label for="cat_edit_business_type">対象業種</label>
                    <select name="business_type_id" id="cat_edit_business_type">
                        <option value="">全業種共通</option>
                        @foreach ($businessTypes as $bt)
                        <option value="{{ $bt->id }}">{{ $bt->name }}</option>
                        @endforeach
                    </select>
                    <p style="font-size:0.72rem;color:#999;margin-top:3px;">未選択の場合、すべての業種の口コミフォームに表示されます</p>
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
                    <div class="emoji-picker-wrap" id="emojiWrapAdd">
                        <div class="form-row">
                            <input type="text" name="icon" id="theme_icon" required placeholder="例: 💰" maxlength="10">
                            <button type="button" class="emoji-picker-btn" onclick="toggleEmojiPanel('emojiWrapAdd')" title="絵文字を選ぶ">😀</button>
                            <span class="icon-preview" id="iconPreview"></span>
                        </div>
                        <div class="emoji-panel" data-target="theme_icon"></div>
                    </div>
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
                    <div class="emoji-picker-wrap" id="emojiWrapEdit">
                        <div class="form-row">
                            <input type="text" name="icon" id="theme_edit_icon" required maxlength="10">
                            <button type="button" class="emoji-picker-btn" onclick="toggleEmojiPanel('emojiWrapEdit')" title="絵文字を選ぶ">😀</button>
                            <span class="icon-preview" id="editIconPreview"></span>
                        </div>
                        <div class="emoji-panel" data-target="theme_edit_icon"></div>
                    </div>
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
    function openAiModal() {
        document.getElementById('aiModal').classList.add('active');
        document.getElementById('aiResults').style.display = 'none';
        document.getElementById('aiResultsList').innerHTML = '';
        document.getElementById('aiStatus').textContent = '';
        document.getElementById('aiApplyBtn').style.display = 'none';
    }

    let aiData = null;

    async function generateAiThemes() {
        const btn = document.getElementById('aiGenerateBtn');
        const status = document.getElementById('aiStatus');
        const businessTypeId = document.getElementById('ai_business_type').value;
        btn.disabled = true;
        btn.textContent = '生成中…';
        status.textContent = '🤖 AI が考えています（10〜30 秒）…';
        document.getElementById('aiResults').style.display = 'none';
        document.getElementById('aiApplyBtn').style.display = 'none';

        try {
            const res = await fetch('/admin/suggestion-themes/ai-suggest', {
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
            html += '<input type="checkbox" class="ai-cat-check" data-ci="' + ci + '" checked onchange="toggleCategoryThemes(' + ci + ')">';
            html += '📁 ' + escapeHtml(cat.name);
            html += '</label>';
            html += '<div style="padding-left:24px;display:flex;flex-wrap:wrap;gap:6px;">';
            cat.themes.forEach(function(t, ti) {
                html += '<label style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;background:#f0f4ff;border:1px solid #e0e5f0;border-radius:16px;font-size:0.8rem;cursor:pointer;">';
                html += '<input type="checkbox" class="ai-theme-check" data-ci="' + ci + '" data-ti="' + ti + '" checked>';
                html += escapeHtml(t.icon) + ' ' + escapeHtml(t.label);
                html += '<span style="color:#888;font-size:0.7rem;">(' + escapeHtml(t.keyword.substring(0, 20)) + (t.keyword.length > 20 ? '…' : '') + ')</span>';
                html += '</label>';
            });
            html += '</div></div>';
        });
        document.getElementById('aiResultsList').innerHTML = html;
    }

    function toggleCategoryThemes(ci) {
        const catChecked = document.querySelector('.ai-cat-check[data-ci="' + ci + '"]').checked;
        document.querySelectorAll('.ai-theme-check[data-ci="' + ci + '"]').forEach(function(cb) {
            cb.disabled = !catChecked;
            if (!catChecked) cb.checked = false;
            else cb.checked = true;
        });
    }

    async function applyAiThemes() {
        if (!aiData) return;
        const businessTypeId = document.getElementById('ai_business_type').value;
        const selected = [];

        aiData.categories.forEach(function(cat, ci) {
            const catChecked = document.querySelector('.ai-cat-check[data-ci="' + ci + '"]').checked;
            if (!catChecked) return;
            const themes = [];
            cat.themes.forEach(function(t, ti) {
                const cb = document.querySelector('.ai-theme-check[data-ci="' + ci + '"][data-ti="' + ti + '"]');
                if (cb && cb.checked) themes.push(t);
            });
            if (themes.length === 0) return;
            selected.push({ name: cat.name, themes: themes });
        });

        if (selected.length === 0) {
            alert('追加するカテゴリ／テーマを選択してください。');
            return;
        }

        const btn = document.getElementById('aiApplyBtn');
        btn.disabled = true;
        btn.textContent = '保存中…';
        try {
            const res = await fetch('/admin/suggestion-themes/ai-apply', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ business_type_id: businessTypeId || null, categories: selected }),
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

    function openCategoryEditModal(id, name, businessTypeId, sortOrder, isActive) {
        document.getElementById('categoryEditForm').action = '/admin/suggestion-themes/categories/' + id;
        document.getElementById('cat_edit_name').value = name;
        document.getElementById('cat_edit_business_type').value = businessTypeId || '';
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

    // ==========================================
    // 絵文字ピッカー
    // ==========================================
    const emojiSections = [
        { title: '表情',     emojis: ['😀','😊','😍','🥰','😎','🤩','😋','🤤','😂','🥺','😇','🤗','😏','😌','🥳','🫡'] },
        { title: '飲食',     emojis: ['🍖','🥩','🍗','🥓','🔥','🍽️','🍚','🍜','🍻','🍺','🥂','🍷','🧊','🥤','☕','🍵','🍰','🍙','🥗','🫓'] },
        { title: 'お金・仕事', emojis: ['💰','💴','💵','💎','🏷️','🎫','💳','🏦','📊','📈','🤝','👔','💼','🔑','🏆','⭐'] },
        { title: 'ジェスチャー', emojis: ['👍','👏','🙌','💪','🤞','✌️','🫶','❤️','💕','✨','🎉','🎊','👋','🙏','💁','🤙'] },
        { title: '乗り物・場所', emojis: ['🚗','🚙','🏎️','🏠','🏢','🏪','📍','🗺️','🛒','🎯','🅿️','🚉','✈️','🚢','🏖️','⛽'] },
        { title: '記号・マーク', emojis: ['⭕','❌','⚡','🔔','📢','📌','🏅','🎖️','💡','🔥','🌟','💫','♻️','🔰','📝','🛡️'] },
        { title: '自然・天気', emojis: ['🌸','🌺','🍀','🌻','🌈','☀️','🌙','❄️','🌊','🌴','🍃','🌾','🐾','🦋','🐟','🌵'] },
    ];

    // パネルの中身を生成（初回1回だけ）
    document.querySelectorAll('.emoji-panel').forEach(function(panel) {
        let html = '';
        emojiSections.forEach(function(sec) {
            html += '<div class="emoji-panel-section">';
            html += '<div class="emoji-panel-section-title">' + sec.title + '</div>';
            html += '<div class="emoji-panel-grid">';
            sec.emojis.forEach(function(e) {
                html += '<button type="button" data-emoji="' + e + '">' + e + '</button>';
            });
            html += '</div></div>';
        });
        panel.innerHTML = html;

        // 絵文字クリック時
        panel.addEventListener('click', function(ev) {
            const btn = ev.target.closest('[data-emoji]');
            if (!btn) return;
            const targetId = panel.dataset.target;
            const input = document.getElementById(targetId);
            input.value = btn.dataset.emoji;
            input.dispatchEvent(new Event('input'));
            panel.classList.remove('active');
        });
    });

    function toggleEmojiPanel(wrapId) {
        const wrap = document.getElementById(wrapId);
        const panel = wrap.querySelector('.emoji-panel');
        // 他のパネルを閉じる
        document.querySelectorAll('.emoji-panel.active').forEach(function(p) {
            if (p !== panel) p.classList.remove('active');
        });
        panel.classList.toggle('active');
    }

    // パネル外クリックで閉じる
    document.addEventListener('click', function(ev) {
        if (!ev.target.closest('.emoji-picker-wrap')) {
            document.querySelectorAll('.emoji-panel.active').forEach(function(p) {
                p.classList.remove('active');
            });
        }
    });
</script>
@endpush
