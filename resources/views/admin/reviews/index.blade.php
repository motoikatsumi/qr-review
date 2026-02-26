@extends('layouts.admin')

@section('title', '口コミ一覧')

@section('content')
<div class="page-header">
    <h1>📝 口コミ一覧</h1>
    <a href="/admin/reviews/export?{{ http_build_query(request()->query()) }}" class="btn btn-secondary">📥 CSVエクスポート</a>
</div>

{{-- フィルター --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" action="/admin/reviews" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin:0;flex:1;min-width:150px;">
                <label>店舗</label>
                <select name="store_id">
                    <option value="">すべて</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:150px;">
                <label>評価フィルター</label>
                <select name="rating_filter">
                    <option value="">すべて</option>
                    <option value="low" {{ request('rating_filter') === 'low' ? 'selected' : '' }}>低評価（1〜3星）</option>
                    <option value="high" {{ request('rating_filter') === 'high' ? 'selected' : '' }}>高評価（4〜5星）</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="height:38px;">絞り込む</button>
        </form>
    </div>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>日時</th>
                <th>店舗</th>
                <th>評価</th>
                <th>コメント</th>
                <th>ステータス</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reviews as $review)
            <tr>
                <td style="white-space:nowrap;font-size:0.8rem;color:#888;">
                    {{ $review->created_at->format('m/d H:i') }}
                </td>
                <td><strong>{{ $review->store->name }}</strong></td>
                <td>
                    <span class="stars">
                        {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
                    </span>
                </td>
                <td style="max-width:300px;">
                    <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $review->comment }}
                    </div>
                    @if($review->ai_generated_text)
                        <div style="font-size:0.75rem;color:#667eea;margin-top:4px;">
                            🤖 AI生成文あり
                        </div>
                    @endif
                </td>
                <td>
                    @if($review->status === 'email_sent')
                        <span class="badge badge-red">メール送信</span>
                    @else
                        <span class="badge badge-green">Google誘導</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:40px;color:#888;">
                    口コミはまだありません。
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($reviews->hasPages())
<div style="margin-top:20px;">
    {{ $reviews->appends(request()->query())->links() }}
</div>
@endif
@endsection
