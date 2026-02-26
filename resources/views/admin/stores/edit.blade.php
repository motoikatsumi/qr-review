@extends('layouts.admin')

@section('title', '店舗を編集')

@section('content')
<div class="page-header">
    <h1>🏪 店舗を編集：{{ $store->name }}</h1>
    <a href="/admin/stores" class="btn btn-secondary">← 一覧に戻る</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/stores/{{ $store->id }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">店舗名 <span style="color:#ef4444">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $store->name) }}" required>
                @error('name') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="google_review_url">Google口コミ投稿URL <span style="color:#ef4444">*</span></label>
                <input type="url" id="google_review_url" name="google_review_url" value="{{ old('google_review_url', $store->google_review_url) }}" required>
                @error('google_review_url') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="notify_email">低評価通知先メール <span style="color:#ef4444">*</span></label>
                <input type="email" id="notify_email" name="notify_email" value="{{ old('notify_email', $store->notify_email) }}" required>
                @error('notify_email') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="slug">スラッグ（URL識別子） <span style="color:#ef4444">*</span></label>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $store->slug) }}" required>
                <p class="form-hint">URL: https://ドメイン/review/<strong>{{ $store->slug }}</strong></p>
                @error('slug') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $store->is_active) ? 'checked' : '' }}>
                    有効（レビュー受付中）
                </label>
            </div>

            <div style="margin-top:28px;">
                <button type="submit" class="btn btn-primary" style="width:auto;padding:12px 32px;">更新する</button>
            </div>
        </form>
    </div>
</div>
@endsection
