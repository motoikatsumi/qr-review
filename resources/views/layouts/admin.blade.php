<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '管理画面') - QRレビュー管理</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Hiragino Sans', 'Noto Sans JP', sans-serif;
            background: #f0f2f5;
            color: #333;
        }
        .navbar {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        .navbar-brand {
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
        }
        .navbar-nav a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .navbar-nav a:hover, .navbar-nav a.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .navbar-right .user-name {
            color: rgba(255,255,255,0.7);
            font-size: 0.8rem;
        }
        .logout-btn {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.8);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px;
        }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .page-header h1 {
            font-size: 1.4rem;
            color: #1e1b4b;
        }
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .card-header {
            padding: 16px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .card-body {
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            padding: 12px 16px;
            text-align: left;
            font-size: 0.8rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
            background: #fafafa;
        }
        table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.9rem;
        }
        table tr:hover {
            background: #f8f9ff;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #555;
        }
        .btn-secondary:hover { background: #e0e0e0; }
        .btn-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .btn-danger:hover { background: #fee2e2; }
        .btn-info {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
        .btn-info:hover { background: #dbeafe; }
        .btn-group {
            display: flex;
            gap: 6px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: border-color 0.2s;
            outline: none;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
        }
        .form-hint {
            font-size: 0.75rem;
            color: #999;
            margin-top: 4px;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fef2f2; color: #dc2626; }
        .badge-gray { background: #f3f4f6; color: #6b7280; }
        .stars { color: #fbbf24; font-size: 1rem; }
        .pagination {
            display: flex;
            gap: 4px;
            justify-content: center;
            margin-top: 20px;
            list-style: none;
        }
        .pagination li a,
        .pagination li span {
            display: block;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            text-decoration: none;
            color: #555;
            background: white;
            border: 1px solid #e5e7eb;
        }
        .pagination li.active span {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        @media (max-width: 768px) {
            .container { padding: 16px; }
            .page-header { flex-direction: column; gap: 12px; align-items: flex-start; }
            .navbar-nav { display: none; }
            table { font-size: 0.8rem; }
            table th, table td { padding: 8px; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="navbar">
        <a href="/admin/stores" class="navbar-brand">📊 QRレビュー管理</a>
        <ul class="navbar-nav">
            <li><a href="/admin/dashboard" class="{{ request()->is('admin/dashboard*') ? 'active' : '' }}">📊 統計</a></li>
            <li><a href="/admin/stores" class="{{ request()->is('admin/stores*') ? 'active' : '' }}">🏪 店舗管理</a></li>
            <li><a href="/admin/reviews" class="{{ request()->is('admin/reviews*') ? 'active' : '' }}">📝 口コミ一覧</a></li>
            <li><a href="/admin/suggestion-themes" class="{{ request()->is('admin/suggestion-themes*') ? 'active' : '' }}">🏷️ テーマ</a></li>
            @if(Auth::user() && Auth::user()->isAdmin())
            <li><a href="/admin/users" class="{{ request()->is('admin/users*') ? 'active' : '' }}">👥 ユーザー</a></li>
            @endif
        </ul>
        <div class="navbar-right">
            <span class="user-name">{{ Auth::user()->name ?? '' }}</span>
            <form method="POST" action="/admin/logout" style="display:inline;">
                @csrf
                <button type="submit" class="logout-btn">ログアウト</button>
            </form>
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

    @stack('scripts')
</body>
</html>
