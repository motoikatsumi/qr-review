@extends('layouts.admin')

@section('title', 'ユーザー追加')

@section('content')
<div class="page-header">
    <h1>👥 ユーザー追加</h1>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/users">
            @csrf

            <div class="form-group">
                <label>名前</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label>メールアドレス</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
                @error('email')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label>パスワード</label>
                <input type="password" name="password" required>
                <p class="form-hint">8文字以上で設定してください</p>
                @error('password')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group">
                <label>パスワード（確認）</label>
                <input type="password" name="password_confirmation" required>
            </div>

            <div class="form-group">
                <label>権限</label>
                <select name="role">
                    <option value="member" {{ old('role') === 'member' ? 'selected' : '' }}>メンバー（閲覧のみ）</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>管理者（全権限）</option>
                </select>
                <p class="form-hint">管理者：ユーザー管理、口コミ削除が可能 ／ メンバー：閲覧・CSV出力のみ</p>
                @error('role')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div style="display:flex;gap:10px;margin-top:24px;">
                <button type="submit" class="btn btn-primary">追加する</button>
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
