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
                <label for="ludocid">ludocid（Google CID）</label>
                <input type="text" id="ludocid" name="ludocid" value="{{ old('ludocid', $store->ludocid) }}" placeholder="例：17082321696390119467">
                <div style="margin-top:6px;">
                    <input type="text" id="cid_helper" placeholder="Google MapsのURLを貼り付けて自動変換" style="border:2px dashed #d1d5db;background:#fafafa;">
                    <p id="cid_result" style="font-size:0.75rem;color:#059669;margin-top:4px;display:none;"></p>
                </div>
                <p class="form-hint">Google MapsのURLを上の欄に貼ると自動でludocidが抽出されます</p>
                @error('ludocid') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="meo_keywords">MEOキーワード</label>
                <input type="text" id="meo_keywords" name="meo_keywords" value="{{ old('meo_keywords', $store->meo_keywords) }}" placeholder="例：買取,質屋,査定,ブランド,金,時計">
                <p class="form-hint">Googleマップ遷移時にURLへ付与するキーワード（カンマ区切り）。空の場合はデフォルトキーワードが使用されます</p>
                @error('meo_keywords') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="meo_ratio">MEO検索URL比率（%） <span style="color:#ef4444">*</span></label>
                <input type="number" id="meo_ratio" name="meo_ratio" value="{{ old('meo_ratio', $store->meo_ratio) }}" min="0" max="100" required>
                <p class="form-hint">Google Maps検索URL経由でMEOシグナルを送る割合。0%＝常にレビュー直接表示、100%＝常に検索経由</p>
                @error('meo_ratio') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
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

@push('scripts')
<script>
document.getElementById('cid_helper').addEventListener('input', function() {
    var url = this.value;
    var match = url.match(/0x([0-9a-fA-F]+)/g);
    var result = document.getElementById('cid_result');
    if (match && match.length >= 2) {
        var hex = match[1].replace('0x', '');
        var dec = BigInt('0x' + hex).toString();
        document.getElementById('ludocid').value = dec;
        result.textContent = '✅ 変換完了: ' + dec;
        result.style.display = 'block';
    } else if (match && match.length === 1) {
        var hex = match[0].replace('0x', '');
        var dec = BigInt('0x' + hex).toString();
        document.getElementById('ludocid').value = dec;
        result.textContent = '✅ 変換完了: ' + dec;
        result.style.display = 'block';
    } else {
        result.textContent = '❌ URLからCIDを検出できませんでした';
        result.style.color = '#dc2626';
        result.style.display = 'block';
    }
});
</script>
@endpush
