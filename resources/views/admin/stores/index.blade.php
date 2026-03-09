@extends('layouts.admin')

@section('title', '店舗管理')

@section('content')
<div class="page-header">
    <h1>🏪 店舗管理</h1>
    <a href="/admin/stores/create" class="btn btn-primary">＋ 新規店舗を追加</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>店舗名</th>
                <th>スラッグ</th>
                <th>通知先メール</th>
                <th>口コミ数</th>
                <th>平均評価</th>
                <th>ステータス</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stores as $store)
            <tr>
                <td><strong>{{ $store->name }}</strong></td>
                <td style="color:#888;font-size:0.8rem;">{{ $store->slug }}</td>
                <td style="font-size:0.85rem;">{{ $store->notify_email }}</td>
                <td>{{ $store->reviews_count }}</td>
                <td>
                    @if($store->reviews_count > 0)
                        <span class="stars">{{ number_format($store->reviews_avg_rating, 1) }}</span>
                        <span style="font-size:0.75rem;color:#888;">/ 5.0</span>
                    @else
                        <span style="color:#ccc;">-</span>
                    @endif
                </td>
                <td>
                    @if($store->is_active)
                        <span class="badge badge-green">有効</span>
                    @else
                        <span class="badge badge-gray">無効</span>
                    @endif
                </td>
                <td>
                    <div class="btn-group">
                        <a href="/admin/stores/{{ $store->id }}/qrcode" class="btn btn-info btn-sm">QR</a>
                        <a href="/admin/stores/{{ $store->id }}/edit" class="btn btn-secondary btn-sm">編集</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:40px;color:#888;">
                    まだ店舗が登録されていません。<br>
                    <a href="/admin/stores/create" style="color:#667eea;">新規店舗を追加しましょう →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
