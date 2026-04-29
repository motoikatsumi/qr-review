@extends('layouts.super-admin')

@section('title', 'パスワード変更')

@section('content')
<div class="page-header">
    <h1>🔑 パスワード変更</h1>
</div>

<div class="card" style="max-width:500px;">
    <div class="card-body">
        <form method="POST" action="{{ url('/super-admin/password') }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="current_password">現在のパスワード <span style="color:#ef4444">*</span></label>
                <input type="password" id="current_password" name="current_password" required>
                @error('current_password') <p style="color:#ef4444;font-size:0.8rem;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="password">新しいパスワード <span style="color:#ef4444">*</span></label>
                <input type="password" id="password" name="password" required minlength="8">
                <p class="form-hint">8文字以上</p>
                @error('password') <p style="color:#ef4444;font-size:0.8rem;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">新しいパスワード（確認） <span style="color:#ef4444">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8">
            </div>

            <button type="submit" class="btn btn-primary">パスワードを変更</button>
        </form>
    </div>
</div>
@endsection
