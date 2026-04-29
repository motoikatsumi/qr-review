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
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="例：○○渋谷店">
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
                <input type="email" id="notify_email" name="notify_email" value="{{ old('notify_email') }}" required>
                @error('notify_email') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="notify_threshold">低評価通知の閾値（★この数以下で通知）</label>
                <select id="notify_threshold" name="notify_threshold">
                    @for($i = 1; $i <= 4; $i++)
                    <option value="{{ $i }}" {{ old('notify_threshold', 3) == $i ? 'selected' : '' }}>
                        ★{{ $i }}以下で通知
                    </option>
                    @endfor
                </select>
                <p class="form-hint">QR口コミ：閾値を超える評価はGoogle誘導、以下はメール通知。Google口コミ：閾値以下で通知メール送信。</p>
                @error('notify_threshold') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="business_type_id">業種 <span style="color:#ef4444">*</span></label>
                <select id="business_type_id" name="business_type_id">
                    <option value="">（未選択）</option>
                    @foreach($businessTypes as $bt)
                    <option value="{{ $bt->id }}" {{ old('business_type_id') == $bt->id ? 'selected' : '' }}>
                        {{ $bt->name }}
                    </option>
                    @endforeach
                </select>
                <p class="form-hint">AI文章生成の切り口・スタイル・NGワードが業種ごとに切り替わります。</p>
                @error('business_type_id') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="slug">URL識別名</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="例: assist-shibuya（空なら自動生成）">
                <p class="form-hint">口コミURLの一部になります。例: https://ドメイン/review/<strong>assist-shibuya</strong> ←この部分です。空欄なら店舗名から自動作成されます。</p>
                @error('slug') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div style="margin-top:28px;">
                <button type="submit" class="btn btn-primary" style="width:auto;padding:12px 32px;">店舗を追加する</button>
            </div>
        </form>
    </div>
</div>
@endsection


