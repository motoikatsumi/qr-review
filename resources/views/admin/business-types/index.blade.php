@extends('layouts.admin')

@section('title', '業種管理')

@section('content')
<div class="page-header">
    <h1>🏢 業種管理</h1>
    <div style="display:flex;gap:12px;align-items:center;">
        @include('admin._partials.trash-filter', ['baseUrl' => '/admin/business-types'])
        @if(!$showTrashed)
        <a href="/admin/business-types/create" class="btn btn-primary">＋ 新規業種を追加</a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

@if(!$showTrashed)
<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:0.85rem;color:#78350f;">
    💡 <strong>業種とは？</strong><br>
    店舗のジャンル（質屋・焼肉店・美容室など）を登録します。業種ごとに AI 文章生成の傾向やレビューフォームの質問項目をカスタマイズできます。<br>
    通常は最初に作った業種のまま運用できるので、<strong>日常的には変更不要</strong>です。
</div>

@if($businessTypes->isNotEmpty())
<div class="card" style="margin-bottom:14px;padding:14px 18px;">
    <input type="search" id="btSearchInput" placeholder="🔍 業種名で絞り込み"
           style="width:100%;padding:9px 14px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.9rem;outline:none;"
           oninput="filterBtRows(this.value)">
    <p style="font-size:0.74rem;color:#9ca3af;margin-top:6px;" id="btSearchHint"></p>
</div>
@endif
@endif

<div class="card">
    <table>
        <thead>
            <tr>
                <th>順序</th>
                <th>業種名</th>
                <th>質屋機能</th>
                <th>投稿</th>
                <th>登録店舗数</th>
                <th>ステータス</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="btTbody">
            @forelse($businessTypes as $bt)
            <tr class="searchable-row">
                <td style="text-align:center;color:#888;">{{ $bt->sort_order }}</td>
                <td><strong>{{ $bt->name }}</strong></td>
                <td style="text-align:center;">
                    @if($bt->use_pawn_system)
                        <span class="badge badge-green">有効</span>
                    @else
                        <span class="badge badge-gray">無効</span>
                    @endif
                </td>
                <td style="text-align:center;">
                    @if($bt->use_purchase_posts)
                        <span class="badge badge-green">有効</span>
                    @else
                        <span class="badge badge-gray">無効</span>
                    @endif
                </td>
                <td style="text-align:center;">{{ $bt->stores_count ?? $bt->stores()->count() }} 店舗</td>
                <td>
                    @if($bt->is_active)
                        <span class="badge badge-green">有効</span>
                    @else
                        <span class="badge badge-gray">無効</span>
                    @endif
                </td>
                <td>
                    @if($showTrashed)
                        <span style="font-size:0.74rem;color:#92400e;display:block;margin-bottom:4px;">削除日: {{ $bt->deleted_at?->format('Y/n/j H:i') }}</span>
                        <form method="POST" action="/admin/business-types/{{ $bt->id }}/restore" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">↩ 復元</button>
                        </form>
                        <form method="POST" action="/admin/business-types/{{ $bt->id }}/force-delete" style="display:inline;"
                              onsubmit="return confirm('「{{ $bt->name }}」を完全に削除します。\nこの操作は元に戻せません。');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">🗑 完全削除</button>
                        </form>
                    @else
                        <a href="/admin/business-types/{{ $bt->id }}/edit" class="btn btn-sm btn-secondary">編集</a>
                        @if(!$bt->stores()->exists())
                        <form method="POST" action="/admin/business-types/{{ $bt->id }}" style="display:inline;" onsubmit="return confirm('この業種をゴミ箱に移動しますか？\n（後で復元できます）')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">🗑 削除</button>
                        </form>
                        @endif
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;color:#888;padding:40px;">
                    @if($showTrashed)
                        🗑 ゴミ箱は空です。
                    @else
                        <div style="padding:20px 10px;">
                            <div style="font-size:3rem;margin-bottom:12px;">🏢</div>
                            <div style="font-size:1.05rem;color:#374151;font-weight:600;margin-bottom:6px;">業種が登録されていません</div>
                            <div style="font-size:0.85rem;color:#6b7280;margin-bottom:18px;">最初の業種を登録してください</div>
                            <a href="/admin/business-types/create" class="btn btn-primary" style="padding:10px 24px;">＋ 業種を追加</a>
                        </div>
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
function filterBtRows(query) {
    var q = (query || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#btTbody tr.searchable-row');
    var hint = document.getElementById('btSearchHint');
    var visibleCount = 0;
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        var match = q === '' || text.indexOf(q) !== -1;
        row.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });
    if (hint) hint.textContent = q === '' ? '' : (visibleCount + ' / ' + rows.length + ' 件 表示中');
}
</script>
@endsection
