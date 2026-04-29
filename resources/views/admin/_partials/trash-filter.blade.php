{{--
  ゴミ箱フィルタ用トグル

  Usage: @include('admin._partials.trash-filter', [
      'showTrashed'   => $showTrashed,
      'trashedCount'  => $trashedCount,
      'baseUrl'       => '/admin/stores',
  ])
--}}
@php
    $showTrashed   = $showTrashed   ?? false;
    $trashedCount  = $trashedCount  ?? 0;
    $baseUrl       = $baseUrl       ?? url()->current();
@endphp
<div class="trash-filter-toggle" style="display:inline-flex;background:#f3f4f6;border-radius:8px;padding:3px;gap:2px;">
    <a href="{{ $baseUrl }}"
       style="padding:6px 14px;border-radius:6px;text-decoration:none;font-size:0.82rem;font-weight:600;
              {{ !$showTrashed ? 'background:white;color:#1e1b4b;box-shadow:0 1px 2px rgba(0,0,0,0.06);' : 'color:#6b7280;' }}">
        ✅ 表示中
    </a>
    <a href="{{ $baseUrl }}?show=trashed"
       style="padding:6px 14px;border-radius:6px;text-decoration:none;font-size:0.82rem;font-weight:600;
              {{ $showTrashed ? 'background:white;color:#92400e;box-shadow:0 1px 2px rgba(0,0,0,0.06);' : 'color:#6b7280;' }}">
        🗑 ゴミ箱
        @if($trashedCount > 0)
            <span style="background:#fef2f2;color:#dc2626;padding:1px 7px;border-radius:10px;font-size:0.7rem;margin-left:4px;">{{ $trashedCount }}</span>
        @endif
    </a>
</div>
@if($showTrashed)
<div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:10px 14px;border-radius:6px;margin-top:12px;font-size:0.82rem;color:#92400e;line-height:1.6;">
    🗑 <strong>ゴミ箱</strong>を表示しています。削除済みの項目は復元 / 完全削除できます。
</div>
@endif
