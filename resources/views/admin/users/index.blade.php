@extends('layouts.admin')

@section('title', 'ユーザー管理')

@section('content')
<div class="page-header">
    <h1>👥 ユーザー管理</h1>
    <a href="/admin/users/create" class="btn btn-primary">＋ ユーザー追加</a>
</div>

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
        <tbody>
            @foreach($users as $user)
            <tr>
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
                    @else
                        <span class="badge badge-role-member">メンバー</span>
                    @endif
                </td>
                <td style="font-size:0.85rem;color:#888;">{{ $user->created_at->format('Y/m/d') }}</td>
                <td>
                    <div class="btn-group">
                        <a href="/admin/users/{{ $user->id }}/edit" class="btn btn-info btn-sm">✏️ 編集</a>
                        @if($user->id !== auth()->id())
                        <form method="POST" action="/admin/users/{{ $user->id }}" onsubmit="return confirm('{{ $user->name }} を削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">🗑 削除</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

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
</style>
@endpush
@endsection
