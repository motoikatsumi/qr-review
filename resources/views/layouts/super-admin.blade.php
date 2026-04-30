<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '運営管理') - QRレビュー</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Hiragino Sans', 'Noto Sans JP', sans-serif;
            background: #f0f2f5;
            color: #333;
        }
        .navbar {
            background: linear-gradient(135deg, #7c2d12 0%, #c2410c 100%);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
        }
        .navbar-brand {
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
        }
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .navbar-right a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.9rem;
        }
        .navbar-right a:hover { color: white; }
        /* ナビゲーションリンク(super-admin) */
        .sa-nav-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.85rem;
            padding: 6px 14px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .sa-nav-link:hover {
            background: rgba(255,255,255,0.12);
            color: white;
        }
        .sa-nav-link.active {
            background: rgba(255,255,255,0.95);
            color: #c2410c !important;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .container {
            max-width: 1200px;
            margin: 24px auto;
            padding: 0 16px;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            font-size: 1rem;
        }
        .card-body { padding: 20px; }

        /* card-body 内のテーブルは自動で横スクロール対応 */
        .card-body { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.88rem;
            vertical-align: middle;
        }
        th { background: #f9fafb; font-weight: 600; white-space: nowrap; }
        td .badge { white-space: nowrap; font-size: 0.72rem; }
        /* 「データなし」セル等は中央寄せで折り返し可 */
        td[colspan] { white-space: normal; }

        /* レスポンシブ：狭い画面ではフォントを少し小さく */
        @media (max-width: 768px) {
            th, td { padding: 6px 8px; font-size: 0.78rem; }
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            cursor: pointer;
            border: none;
        }
        .btn-primary { background: #c2410c; color: white; }
        .btn-primary:hover { background: #9a3412; }
        .btn-secondary { background: #e5e7eb; color: #333; }
        .btn-secondary:hover { background: #d1d5db; }
        .btn-sm { padding: 4px 10px; font-size: 0.8rem; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 4px; font-weight: 500; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .form-hint { font-size: 0.8rem; color: #6b7280; margin-top: 4px; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .page-header h1 { font-size: 1.3rem; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 768px) {
            .two-col { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div style="display:flex;align-items:center;gap:24px;">
            <a href="{{ url('/super-admin/dashboard') }}" class="navbar-brand">🛡️ QRレビュー 運営管理</a>
            @auth('super_admin')
                <a href="{{ url('/super-admin/dashboard') }}" class="sa-nav-link {{ request()->is('super-admin/dashboard*') ? 'active' : '' }}">ダッシュボード</a>
                <a href="{{ url('/super-admin/tenants') }}" class="sa-nav-link {{ request()->is('super-admin/tenants*') ? 'active' : '' }}">テナント一覧</a>
                <a href="{{ url('/super-admin/invoices') }}" class="sa-nav-link {{ request()->is('super-admin/invoices*') ? 'active' : '' }}">📄 請求書</a>
                <a href="{{ url('/super-admin/manual') }}" class="sa-nav-link {{ request()->is('super-admin/manual*') ? 'active' : '' }}">📖 マニュアル</a>
            @endauth
        </div>
        <div class="navbar-right">
            @auth('super_admin')
                <span style="color:rgba(255,255,255,0.7);font-size:0.85rem;">{{ auth('super_admin')->user()->name }}</span>
                <a href="{{ url('/super-admin/password') }}">パスワード変更</a>
                <form method="POST" action="{{ url('/super-admin/logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" style="background:none;border:none;color:rgba(255,255,255,0.8);cursor:pointer;font-size:0.85rem;">ログアウト</button>
                </form>
            @endauth
        </div>
    </nav>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @yield('content')
    </div>
</body>
</html>
