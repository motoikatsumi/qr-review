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
                <select name="role" id="role-select" onchange="toggleStoreField()">
                    <option value="member" {{ old('role', $user->role) === 'member' ? 'selected' : '' }}>メンバー（閲覧のみ）</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>管理者（全権限）</option>
                    <option value="store_owner" {{ old('role', $user->role) === 'store_owner' ? 'selected' : '' }}>店舗オーナー</option>
                </select>
                <p class="form-hint">管理者：全権限 ／ メンバー：閲覧・CSV出力のみ ／ 店舗オーナー：自店舗管理のみ</p>
                @error('role')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-group" id="store-field" style="display:{{ old('role', $user->role) === 'store_owner' ? 'block' : 'none' }};">
                <label>担当店舗</label>
                <select name="store_id">
                    <option value="">-- 選択してください --</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ old('store_id', $user->store_id) == $store->id ? 'selected' : '' }}>
                            {{ $store->name }}
                        </option>
                    @endforeach
                </select>
                @error('store_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div style="display:flex;gap:10px;margin-top:24px;">
                <button type="submit" class="btn btn-primary">更新する</button>
                <a href="/admin/users" class="btn btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</div>

{{-- 危険な操作（削除）は別カードに分離して誤操作を防ぐ --}}
@if($user->id !== auth()->id())
<div class="card" style="margin-top:20px;border:2px solid #fecaca;">
    <div class="card-body" style="background:#fef2f2;">
        <h3 style="margin:0 0 8px;color:#991b1b;font-size:1rem;">⚠️ 危険な操作</h3>
        <p style="font-size:0.85rem;color:#7f1d1d;margin-bottom:14px;line-height:1.6;">
            このユーザーをゴミ箱に移動します。後でゴミ箱から復元できますが、誤操作にご注意ください。
        </p>
        <form method="POST" action="/admin/users/{{ $user->id }}"
              onsubmit="return confirm('ユーザー「{{ $user->name }}」をゴミ箱に移動します。\nよろしいですか？（後で復元できます）');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">🗑 このユーザーを削除する</button>
        </form>
    </div>
</div>
@else
<div class="card" style="margin-top:20px;background:#fef3c7;border:1px solid #fde68a;">
    <div class="card-body">
        <p style="font-size:0.85rem;color:#92400e;margin:0;">ℹ️ 自分自身は削除できません。</p>
    </div>
</div>
@endif

@push('styles')
<style>
    .form-error { color: #dc2626; font-size: 0.8rem; margin-top: 4px; }
</style>
@endpush
@push('scripts')
<script>
function toggleStoreField() {
    const role = document.getElementById('role-select').value;
    document.getElementById('store-field').style.display = role === 'store_owner' ? 'block' : 'none';
}
</script>
@endpush
@endsection
