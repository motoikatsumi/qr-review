@extends('layouts.admin')

@section('title', 'ユーザー管理')

@section('content')
<div class="page-header">
    <h1>👥 ユーザー管理</h1>
    <div style="display:flex;gap:12px;align-items:center;">
        @include('admin._partials.trash-filter', ['baseUrl' => '/admin/users'])
        @if(!$showTrashed)
        <a href="/admin/users/create" class="btn btn-primary">＋ ユーザー追加</a>
        @endif
    </div>
</div>

@if(!$showTrashed && $users->isNotEmpty())
<div class="card" style="margin-bottom:14px;padding:14px 18px;">
    <input type="search" id="userSearchInput" placeholder="🔍 名前・メールアドレス・権限で絞り込み"
           style="width:100%;padding:9px 14px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.9rem;outline:none;"
           oninput="filterUserRows(this.value)">
    <p style="font-size:0.74rem;color:#9ca3af;margin-top:6px;" id="userSearchHint"></p>
</div>
@endif

<div class="card">
    <table>
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>権限</th>
                <th>登録日</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="userTbody">
            @forelse($users as $user)
            <tr class="searchable-row">
                <td style="font-weight:600;">
                    {{ $user->name }}
                    @if($user->id === auth()->id())
                        <span style="font-size:0.75rem;color:#667eea;font-weight:400;">（自分）</span>
                    @endif
                </td>
                <td>{{ $user->email }}</td>
                <td>
                    @if($user->isAdmin())
                        <span class="badge badge-role-admin">管理者</span>
                    @elseif($user->isStoreOwner())
                        <span class="badge badge-role-owner">店舗オーナー</span>
                        @if($user->store)
                            <span style="font-size:0.75rem;color:#888;">（{{ $user->store->name }}）</span>
                        @endif
                    @else
                        <span class="badge badge-role-member">メンバー</span>
                    @endif
                </td>
                <td style="font-size:0.85rem;color:#888;">{{ $user->created_at->format('Y/m/d') }}</td>
                <td>
                    @if($showTrashed)
                        <div class="btn-group">
                            <span style="font-size:0.74rem;color:#92400e;display:block;margin-bottom:4px;">削除日: {{ $user->deleted_at?->format('Y/n/j H:i') }}</span>
                            <form method="POST" action="/admin/users/{{ $user->id }}/restore" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">↩ 復元</button>
                            </form>
                            <form method="POST" action="/admin/users/{{ $user->id }}/force-delete" style="display:inline;"
                                  onsubmit="return confirm('「{{ $user->name }}」を完全に削除します。\nこの操作は元に戻せません。');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">🗑 完全削除</button>
                            </form>
                        </div>
                    @else
                        {{-- 削除ボタンは編集画面側に移動（誤操作防止） --}}
                        <a href="/admin/users/{{ $user->id }}/edit" class="btn btn-info btn-sm">✏️ 編集</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:40px;color:#888;">
                    @if($showTrashed)
                        🗑 ゴミ箱は空です。
                    @else
                        <div style="padding:30px 20px;">
                            <div style="font-size:3rem;margin-bottom:12px;">👥</div>
                            <div style="font-size:1.05rem;color:#374151;font-weight:600;margin-bottom:6px;">ユーザーが登録されていません</div>
                            <div style="font-size:0.85rem;color:#6b7280;margin-bottom:18px;">店舗オーナーやスタッフを招待しましょう</div>
                            <a href="/admin/users/create" class="btn btn-primary" style="padding:10px 24px;">＋ ユーザーを追加</a>
                        </div>
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
function filterUserRows(query) {
    var q = (query || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#userTbody tr.searchable-row');
    var hint = document.getElementById('userSearchHint');
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

@push('styles')
<style>
    .badge-role-admin {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-role-member {
        background: #f3f4f6;
        color: #6b7280;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-role-owner {
        background: linear-gradient(135deg, #059669, #047857);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>
@endpush
@endsection
