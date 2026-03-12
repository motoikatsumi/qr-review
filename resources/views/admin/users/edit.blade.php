@extends('layouts.admin')

@section('title', 'ユーザー編集')

@section('content')
<div class="page-header">
    <h1>👥 ユーザー編集</h1>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/users/{{ $user->id }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>名前</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label>メールアドレス</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label>パスワード（変更する場合のみ）</label>
                <input type="password" name="password">
                <p class="form-hint">空欄のままなら変更しません</p>
                @error('password')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label>パスワード（確認）</label>
                <input type="password" name="password_confirmation">
            </div>

            <div class="form-group">
                <label>権限</label>
                <select name="role">
                    <option value="member" {{ old('role', $user->role) === 'member' ? 'selected' : '' }}>メンバー（閲覧のみ）</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>管理者（全権限）</option>
                </select>
                <p class="form-hint">管理者：ユーザー管理、口コミ削除が可能 ／ メンバー：閲覧・CSV出力のみ</p>
                @error('role')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div style="display:flex;gap:10px;margin-top:24px;">
                <button type="submit" class="btn btn-primary">更新する</button>
                <a href="/admin/users" class="btn btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    .form-error { color: #dc2626; font-size: 0.8rem; margin-top: 4px; }
</style>
@endpush
@endsection
