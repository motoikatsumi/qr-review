@extends('layouts.store')

@section('title', 'キーワード管理')

@section('content')
<div class="page-header">
    <h1>🏷️ キーワード管理</h1>
</div>

@push('styles')
<style>
    .kw-help { background: linear-gradient(135deg,#f0f9ff,#e0f2fe); border:1px solid #bae6fd; border-radius:12px; padding: 14px 18px; margin-bottom: 20px; font-size: 0.85rem; color:#075985; line-height:1.7; }
    .kw-section { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 20px; overflow:hidden; }
    .kw-section-header { padding: 16px 20px; background: #f8f9fa; border-bottom:1px solid #e5e7eb; font-weight:600; display:flex; align-items:center; gap:8px; }
    .kw-section-body { padding: 16px 20px; }
    .kw-category { border:1px solid #e5e7eb; border-radius:10px; margin-bottom:12px; }
    .kw-category-name { padding:10px 14px; background:#fafafa; border-bottom:1px solid #e5e7eb; font-weight:600; color:#065f46; font-size:0.9rem; }
    .kw-list { padding: 12px 14px; display:flex; gap:8px; flex-wrap:wrap; }
    .kw-tag { background:#ecfdf5; color:#065f46; padding:5px 12px; border-radius:999px; font-size:0.82rem; border:1px solid #a7f3d0; }
    .kw-empty { padding: 20px; color:#888; font-size:0.85rem; text-align:center; }
    .kw-info { font-size:0.8rem; color:#6b7280; margin-top:6px; }
</style>
@endpush

<div class="kw-help">
    📌 業種「<strong>{{ $store->businessType->name ?? '未設定' }}</strong>」に紐付くキーワード一覧です（閲覧のみ）。<br>
    QR 口コミの「テーマ」と Google 返信の「カテゴリ／キーワード」は、AI が文章を生成するときの引き出しになります。<br>
    変更が必要な場合はシステム管理者にご連絡ください。
</div>

{{-- 口コミ提案テーマ --}}
<div class="kw-section">
    <div class="kw-section-header">
        💬 QR 口コミ：テーマ一覧
        <span style="font-weight:400;font-size:0.82rem;color:#888;">（お客様が QR ページで選択するテーマ）</span>
    </div>
    <div class="kw-section-body">
        @forelse($suggestionCategories as $cat)
            <div class="kw-category">
                <div class="kw-category-name">
                    {{ $cat->name }}
                    @if(!$cat->business_type_id)
                        <span style="font-size:0.7rem;color:#6b7280;background:#f3f4f6;padding:2px 8px;border-radius:999px;margin-left:6px;">業種共通</span>
                    @endif
                </div>
                <div class="kw-list">
                    @forelse($cat->activeThemes as $theme)
                        <span class="kw-tag">{{ $theme->icon ?? '' }} {{ $theme->label }}</span>
                    @empty
                        <span style="color:#aaa;font-size:0.82rem;">テーマなし</span>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="kw-empty">この業種に登録されたテーマはありません。</div>
        @endforelse
    </div>
</div>

{{-- 返信カテゴリ --}}
<div class="kw-section">
    <div class="kw-section-header">
        🌐 Google 返信：カテゴリ・キーワード
        <span style="font-weight:400;font-size:0.82rem;color:#888;">（返信文の方向性を決めるラベル）</span>
    </div>
    <div class="kw-section-body">
        @forelse($replyCategories as $cat)
            <div class="kw-category">
                <div class="kw-category-name">
                    {{ $cat->name }}
                    @if(!$cat->business_type_id)
                        <span style="font-size:0.7rem;color:#6b7280;background:#f3f4f6;padding:2px 8px;border-radius:999px;margin-left:6px;">業種共通</span>
                    @endif
                </div>
                <div class="kw-list">
                    @forelse($cat->activeKeywords as $kw)
                        <span class="kw-tag">{{ $kw->label }}</span>
                    @empty
                        <span style="color:#aaa;font-size:0.82rem;">キーワードなし</span>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="kw-empty">この業種に登録された返信カテゴリはありません。</div>
        @endforelse
    </div>
</div>
@endsection
