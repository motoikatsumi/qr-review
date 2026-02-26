@extends('layouts.review')

@section('title', 'ありがとうございます - ' . $store->name)

@push('styles')
<style>
    .thankyou-icon {
        text-align: center;
        font-size: 3.5rem;
        margin-bottom: 16px;
    }
    .store-name {
        font-size: 1rem;
        color: #764ba2;
        font-weight: 600;
        text-align: center;
        margin-bottom: 4px;
    }
    .message {
        text-align: center;
        color: #666;
        font-size: 0.9rem;
        line-height: 1.7;
        margin-top: 16px;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="thankyou-icon">🙏</div>
    <p class="store-name">{{ $store->name }}</p>
    <h1>貴重なご意見ありがとうございます</h1>
    <p class="message">
        お客様のご意見はサービス改善に<br>
        活用させていただきます。<br><br>
        またのご来店を心よりお待ちしております。
    </p>
</div>
@endsection
