@extends('layouts.admin')

@section('title', '店舗管理')

@section('content')
@php $isAdmin = auth()->user()?->isAdmin() ?? false; @endphp

<div class="page-header">
    <h1>🏪 店舗管理</h1>
    <div style="display:flex;gap:12px;align-items:center;">
        @if($isAdmin)
            @include('admin._partials.trash-filter', ['baseUrl' => '/admin/stores'])
            @if(!$showTrashed)
            <a href="/admin/stores/create" class="btn btn-primary">＋ 新規店舗を追加</a>
            @endif
        @endif
    </div>
</div>

@if(!$isAdmin)
<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:0.82rem;color:#075985;">
    🔒 閲覧モード — 店舗の追加・編集・削除には管理者権限が必要です。
</div>
@endif

{{-- ライブ検索ボックス（通常表示時のみ） --}}
@if(!$showTrashed && $stores->isNotEmpty())
<div class="card" style="margin-bottom:14px;padding:14px 18px;">
    <input type="search" id="storeSearchInput" placeholder="🔍 店舗名・URL識別名・メールで絞り込み"
           style="width:100%;padding:9px 14px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.9rem;outline:none;"
           oninput="filterStoreRows(this.value)">
    <p style="font-size:0.74rem;color:#9ca3af;margin-top:6px;" id="storeSearchHint"></p>
</div>
@endif

<div class="card">
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>店舗名</th>
                <th>URL識別名</th>
                <th>通知先メール</th>
                <th style="text-align:center;">システム口コミ</th>
                <th style="text-align:center;">Google口コミ</th>
                <th>平均評価</th>
                <th>ステータス</th>
                {{-- <th>MEO比率</th> --}}
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="storeTbody">
            @forelse($stores as $store)
            <tr class="searchable-row">
                <td style="color:#888;font-size:0.85rem;text-align:center;">{{ $loop->iteration }}</td>
                <td><strong>{{ $store->name }}</strong></td>
                <td style="color:#888;font-size:0.8rem;">{{ $store->slug }}</td>
                <td style="font-size:0.85rem;">{{ $store->notify_email }}</td>
                <td style="text-align:center;">{{ $store->reviews_count }}</td>
                <td style="text-align:center;">{{ $store->google_reviews_count }}</td>
                <td>
                    @if($store->reviews_count > 0)
                        <span class="stars">{{ number_format($store->reviews_avg_rating, 1) }}</span>
                        <span style="font-size:0.75rem;color:#888;">/ 5.0</span>
                    @else
                        <span style="color:#aaa;font-size:0.8rem;">（レビューなし）</span>
                    @endif
                </td>
                <td>
                    @if($store->is_active)
                        <span class="badge badge-green">有効</span>
                    @else
                        <span class="badge badge-gray">無効</span>
                    @endif
                </td>
                {{-- <td style="font-size:0.85rem;">{{ $store->meo_ratio }}%</td> --}}
                <td>
                    @if($showTrashed && $isAdmin)
                        <div class="btn-group">
                            <span style="font-size:0.74rem;color:#92400e;display:block;margin-bottom:4px;">削除日: {{ $store->deleted_at?->format('Y/n/j H:i') }}</span>
                            <form method="POST" action="/admin/stores/{{ $store->id }}/restore" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">↩ 復元</button>
                            </form>
                            <form method="POST" action="/admin/stores/{{ $store->id }}/force-delete" style="display:inline;"
                                  onsubmit="return confirm('「{{ $store->name }}」を完全に削除します。\nこの操作は元に戻せません。本当に削除しますか？');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">🗑 完全削除</button>
                            </form>
                        </div>
                    @else
                        <div class="btn-group">
                            <a href="/admin/stores/{{ $store->id }}/qrcode" class="btn btn-info btn-sm">QR</a>
                            @if($isAdmin)
                                <a href="/admin/stores/{{ $store->id }}/edit" class="btn btn-secondary btn-sm">編集</a>
                                <button type="button" class="btn btn-success btn-sm" onclick="openDuplicateModal({{ $store->id }}, '{{ e($store->name) }}', '{{ e($store->notify_email) }}')" title="この店舗の AI 設定・業種などをコピーして新しい店舗を作成">📋 複製</button>
                            @endif
                        </div>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center;padding:40px;color:#888;">
                    @if($showTrashed)
                        🗑 ゴミ箱は空です。
                    @else
                        <div style="padding:30px 20px;">
                            <div style="font-size:3rem;margin-bottom:12px;">🏪</div>
                            <div style="font-size:1.05rem;color:#374151;font-weight:600;margin-bottom:6px;">まだ店舗が登録されていません</div>
                            @if($isAdmin)
                                <div style="font-size:0.85rem;color:#6b7280;margin-bottom:18px;">最初の店舗を登録して QR コードレビューを始めましょう</div>
                                <a href="/admin/stores/create" class="btn btn-primary" style="padding:10px 24px;">＋ 最初の店舗を追加</a>
                            @else
                                <div style="font-size:0.85rem;color:#6b7280;">店舗の追加には管理者権限が必要です。</div>
                            @endif
                        </div>
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- 店舗複製モーダル --}}
<div id="duplicateModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:14px;width:90%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="padding:18px 24px;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:1rem;display:flex;justify-content:space-between;align-items:center;">
            <span>📋 店舗設定をコピーして新しい店舗を作成</span>
            <button type="button" onclick="closeDuplicateModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#999;padding:4px 8px;">&times;</button>
        </div>
        <form method="POST" id="duplicateForm">
            @csrf
            <div style="padding:20px 24px;">
                <p style="font-size:0.85rem;color:#666;margin-bottom:14px;background:#f0f4ff;padding:10px;border-radius:8px;">
                    コピー元: <strong id="dupSourceName"></strong><br>
                    <span style="font-size:0.78rem;color:#888;">業種・AI 設定・通知先・投稿フッターなどがコピーされます。Google レビュー URL は店舗ごとに異なるため新規入力が必要です。</span>
                </p>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:0.83rem;font-weight:600;color:#555;margin-bottom:5px;">新しい店舗名 <span style="color:#ef4444">*</span></label>
                    <input type="text" name="name" id="dupName" required maxlength="255" style="width:100%;padding:9px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.88rem;outline:none;" placeholder="例: 質屋アシスト 鹿児島中央店">
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:0.83rem;font-weight:600;color:#555;margin-bottom:5px;">Google レビュー URL（後で設定可）</label>
                    <input type="url" name="google_review_url" id="dupGoogleUrl" maxlength="500" style="width:100%;padding:9px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.88rem;outline:none;" placeholder="https://g.page/r/... （空欄可）">
                    <p style="font-size:0.72rem;color:#999;margin-top:3px;">空欄の場合は後で編集画面から設定できます</p>
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:0.83rem;font-weight:600;color:#555;margin-bottom:5px;">通知先メールアドレス</label>
                    <input type="email" name="notify_email" id="dupEmail" maxlength="255" style="width:100%;padding:9px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.88rem;outline:none;" placeholder="既定はコピー元と同じ">
                </div>
            </div>
            <div style="padding:14px 24px;border-top:1px solid #e5e7eb;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="closeDuplicateModal()" class="btn btn-secondary">キャンセル</button>
                <button type="submit" class="btn btn-success">📋 コピーして作成</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openDuplicateModal(storeId, storeName, notifyEmail) {
        document.getElementById('duplicateForm').action = '/admin/stores/' + storeId + '/duplicate';
        document.getElementById('dupSourceName').textContent = storeName;
        document.getElementById('dupName').value = storeName + ' （コピー）';
        document.getElementById('dupEmail').value = notifyEmail || '';
        document.getElementById('dupGoogleUrl').value = '';
        document.getElementById('duplicateModal').style.display = 'flex';
    }
    function closeDuplicateModal() {
        document.getElementById('duplicateModal').style.display = 'none';
    }
    document.getElementById('duplicateModal').addEventListener('click', function(e) {
        if (e.target === this) closeDuplicateModal();
    });

    // ライブ検索（複数カラム横断: 店舗名 / slug / メール）
    function filterStoreRows(query) {
        var q = (query || '').toLowerCase().trim();
        var rows = document.querySelectorAll('#storeTbody tr.searchable-row');
        var hint = document.getElementById('storeSearchHint');
        var visibleCount = 0;
        rows.forEach(function(row) {
            var text = row.textContent.toLowerCase();
            var match = q === '' || text.indexOf(q) !== -1;
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });
        if (hint) hint.textContent = q === '' ? '' : (visibleCount + ' / ' + rows.length + ' 件 表示中');
    }
</script>
@endpush
@endsection
