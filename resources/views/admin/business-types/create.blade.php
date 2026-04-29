@extends('layouts.admin')

@section('title', '業種を追加')

@section('content')
<div class="page-header">
    <h1>🏢 新規業種を追加</h1>
    <a href="/admin/business-types" class="btn btn-secondary">← 一覧に戻る</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/business-types">
            @csrf
            @include('admin.business-types._form', ['businessType' => null])
            <div style="margin-top:28px;">
                <button type="submit" class="btn btn-primary" style="width:auto;padding:12px 32px;">業種を追加する</button>
            </div>
        </form>
    </div>
</div>
@endsection
