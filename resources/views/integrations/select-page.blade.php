@extends('layouts.admin')

@section('title', 'Facebookページを選択')

@section('content')
<div class="page-header">
    <h1>📘 Facebookページを選択 — {{ $store->name }}</h1>
</div>

@push('styles')
<style>
    .page-list { max-width: 600px; }
    .page-item { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 20px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; }
    .page-info { display: flex; align-items: center; gap: 12px; }
    .page-icon { width: 44px; height: 44px; background: #1877f2; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2rem; }
    .page-name { font-weight: 700; font-size: 1rem; }
    .page-meta { font-size: 0.82rem; color: #888; margin-top: 2px; }
    .ig-badge { display: inline-flex; align-items: center; gap: 4px; background: linear-gradient(135deg, #f09433 0%, #dc2743 50%, #bc1888 100%); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.72rem; font-weight: 600; }
</style>
@endpush

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<p style="color:#666; margin-bottom:20px;">複数のFacebookページが見つかりました。連携するページを選択してください。</p>

<div class="page-list">
    @foreach($pages as $page)
        <div class="page-item">
            <div class="page-info">
                <div class="page-icon">f</div>
                <div>
                    <div class="page-name">{{ $page['name'] ?? $page['id'] }}</div>
                    <div class="page-meta">
                        ID: {{ $page['id'] }}
                        @if(!empty($page['instagram_business_account']['username']))
                            <span class="ig-badge">📸 @{{ $page['instagram_business_account']['username'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ url('/meta/save-page') }}">
                @csrf
                <input type="hidden" name="page_id" value="{{ $page['id'] }}">
                <button class="btn btn-primary btn-sm">このページを連携</button>
            </form>
        </div>
    @endforeach
</div>

<p style="font-size: 0.82rem; color: #888; margin-top: 20px;">
    ※ Instagramビジネスアカウントが紐付いているページは、Instagramも自動連携されます。
</p>
@endsection
