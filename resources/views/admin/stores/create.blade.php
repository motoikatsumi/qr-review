@extends('layouts.admin')

@section('title', '店舗を追加')

@section('content')
<div class="page-header">
    <h1>🏪 新規店舗を追加</h1>
    <a href="/admin/stores" class="btn btn-secondary">← 一覧に戻る</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/stores">
            @csrf

            <div class="form-group">
                <label for="name">店舗名 <span style="color:#ef4444">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="例：ASSIST 渋谷店">
                @error('name') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="google_review_url">Google口コミ投稿URL <span style="color:#ef4444">*</span></label>
                <input type="url" id="google_review_url" name="google_review_url" value="{{ old('google_review_url') }}" required placeholder="https://search.google.com/local/writereview?placeid=...">
                <p class="form-hint">Googleビジネスプロフィールの口コミ投稿URLを入力してください</p>
                @error('google_review_url') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="notify_email">低評価通知先メール <span style="color:#ef4444">*</span></label>
                <input type="email" id="notify_email" name="notify_email" value="{{ old('notify_email', 'shichi@assist-grp.jp') }}" required>
                @error('notify_email') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="slug">スラッグ（URL識別子）</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="自動生成されます（任意入力）">
                <p class="form-hint">空の場合は店舗名から自動生成されます。URL: https://ドメイン/review/<strong>スラッグ</strong></p>
                @error('slug') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div style="margin-top:28px;">
                <button type="submit" class="btn btn-primary" style="width:auto;padding:12px 32px;">店舗を追加する</button>
            </div>
        </form>
    </div>
</div>
@endsection
