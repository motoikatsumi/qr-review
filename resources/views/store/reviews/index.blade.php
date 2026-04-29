@extends('layouts.store')

@section('title', 'QR口コミ一覧')

@section('content')
<div class="page-header">
    <h1>📝 QR口コミ一覧</h1>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <select name="rating" style="padding:8px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.88rem;">
                <option value="">全評価</option>
                @for($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>★{{ $i }}</option>
                @endfor
            </select>
            <button class="btn btn-secondary btn-sm" type="submit">絞り込み</button>
            @if(request('rating'))
                <a href="/store/reviews" class="btn btn-secondary btn-sm">リセット</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        @if($reviews->isEmpty())
            <p style="padding:20px;color:#888;font-size:0.9rem;">口コミはまだありません。</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>日時</th>
                        <th>評価</th>
                        <th>コメント</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reviews as $review)
                    <tr>
                        <td style="white-space:nowrap;font-size:0.82rem;color:#888;">{{ $review->created_at->format('Y/m/d H:i') }}</td>
                        <td><span class="stars">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span></td>
                        <td style="max-width:500px;">{{ $review->comment ?: '（コメントなし）' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="padding:16px 20px;">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
