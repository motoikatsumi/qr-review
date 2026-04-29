@extends('layouts.admin')

@section('title', '業種を編集')

@section('content')
<div class="page-header">
    <h1>🏢 業種を編集：{{ $businessType->name }}</h1>
    <a href="/admin/business-types" class="btn btn-secondary">← 一覧に戻る</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/business-types/{{ $businessType->id }}">
            @csrf @method('PUT')
            @include('admin.business-types._form', ['businessType' => $businessType])
            <div style="margin-top:28px;">
                <button type="submit" class="btn btn-primary" style="width:auto;padding:12px 32px;">更新する</button>
            </div>
        </form>
    </div>
</div>
@endsection
