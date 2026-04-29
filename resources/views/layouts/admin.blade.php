<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '管理画面') - QRレビュー管理</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('QRvoice.svg') }}">
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
        .navbar-brand img {
            width: 60px;
            height: 60px;
            display: block;
        }
        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
        }
        .navbar-nav > li {
            position: relative;
        }
        .navbar-nav a,
        .navbar-nav .nav-dropdown-toggle {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            transition: all 0.2s;
            cursor: pointer;
            display: inline-block;
            background: none;
            border: none;
            font-family: inherit;
        }
        .navbar-nav a:hover, .navbar-nav a.active,
        .navbar-nav .nav-dropdown-toggle:hover,
        .navbar-nav li:hover > .nav-dropdown-toggle {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        .nav-dropdown-toggle::after {
            content: ' ▾';
            font-size: 0.7rem;
            opacity: 0.7;
        }
        .nav-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            min-width: 200px;
            padding: 6px 0;
            z-index: 1000;
            margin-top: 0;
        }
        .nav-dropdown::before {
            content: '';
            position: absolute;
            top: -12px;
            left: 0;
            right: 0;
            height: 12px;
        }
        .navbar-nav li:hover > .nav-dropdown {
            display: block;
        }
        .nav-dropdown a {
            color: #374151 !important;
            padding: 10px 18px !important;
            border-radius: 0 !important;
            display: block !important;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        .nav-dropdown a:hover {
            background: #f3f4f6 !important;
            color: #1e1b4b !important;
        }
        .nav-dropdown a.active {
            background: #eef2ff !important;
            color: #4338ca !important;
            font-weight: 600;
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
            max-width: 1400px;
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
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
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
        .pagination li.disabled span {
            color: #aaa;
            background: #f9f9f9;
            cursor: default;
        }
        .pagination li a:hover {
            background: #f3f4f6;
        }
        @media (max-width: 768px) {
            .container { padding: 16px; }
            .page-header { flex-direction: column; gap: 12px; align-items: flex-start; }
            .navbar-nav { display: none; }
            table { font-size: 0.8rem; }
            table th, table td { padding: 8px; }
        }
        /* ④ バリデーションエラーを見やすく */
        p[style*="color:#ef4444"] {
            font-size: 0.9rem !important;
            font-weight: 500;
        }
        /* ⑤ 小さすぎるフォントの底上げ */
        .form-hint { font-size: 0.8rem; }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="navbar">
        <a href="/admin/stores" class="navbar-brand">
            <img src="{{ asset('QRvoice.svg') }}" alt="QRvoiceロゴ">
            <span>QRレビュー管理</span>
        </a>
        <ul class="navbar-nav">
            <li><a href="/admin/dashboard" class="{{ request()->is('admin/dashboard*') ? 'active' : '' }}">🏠 ホーム</a></li>
            <li>
                <span class="nav-dropdown-toggle {{ request()->is('admin/google-reviews*') || request()->is('admin/reviews*') || request()->is('admin/ai-reply-preview*') ? 'active' : '' }}">💬 口コミ</span>
                <ul class="nav-dropdown">
                    <li><a href="/admin/google-reviews" class="{{ request()->is('admin/google-reviews*') ? 'active' : '' }}">🌐 Google 口コミ（返信）</a></li>
                    <li><a href="/admin/reviews" class="{{ request()->is('admin/reviews*') ? 'active' : '' }}">📝 アンケート口コミ</a></li>
                    <li><a href="/admin/ai-reply-preview" class="{{ request()->is('admin/ai-reply-preview*') ? 'active' : '' }}">🔍 AI返信プレビュー＆学習</a></li>
                </ul>
            </li>
            <li><a href="/admin/purchase-posts" class="{{ request()->is('admin/purchase-posts*') ? 'active' : '' }}">📦 投稿</a></li>
            <li><a href="/admin/stores" class="{{ request()->is('admin/stores*') ? 'active' : '' }}">🏪 店舗設定</a></li>
            <li>
                @php
                    try {
                        $unpaidInvoiceCount = \App\Models\Invoice::where('tenant_id', \App\Models\Tenant::current()?->id ?? 0)
                            ->whereIn('status', ['sent', 'overdue'])->count();
                    } catch (\Throwable $e) { $unpaidInvoiceCount = 0; }
                @endphp
                <a href="/admin/invoices" class="{{ request()->is('admin/invoices*') ? 'active' : '' }}">
                    📄 請求書
                    @if($unpaidInvoiceCount > 0)
                        <span style="background:#ef4444;color:white;border-radius:10px;padding:1px 7px;font-size:0.7rem;font-weight:600;margin-left:4px;">{{ $unpaidInvoiceCount }}</span>
                    @endif
                </a>
            </li>
            <li>
                <span class="nav-dropdown-toggle {{ request()->is('admin/business-types*') || request()->is('admin/suggestion-themes*') || request()->is('admin/reply-categories*') || request()->is('admin/google-settings*') || request()->is('admin/users*') ? 'active' : '' }}">⚙️ 詳細設定</span>
                <ul class="nav-dropdown">
                    <li><a href="/admin/suggestion-themes" class="{{ request()->is('admin/suggestion-themes*') ? 'active' : '' }}">🏷️ 口コミテーマ</a></li>
                    <li><a href="/admin/reply-categories" class="{{ request()->is('admin/reply-categories*') ? 'active' : '' }}">💬 返信カテゴリ</a></li>
                    <li><a href="/admin/business-types" class="{{ request()->is('admin/business-types*') ? 'active' : '' }}">🏢 業種マスタ</a></li>
                    @if(Auth::user() && Auth::user()->isAdmin())
                    <li><a href="/admin/google-settings" class="{{ request()->is('admin/google-settings*') ? 'active' : '' }}">🌐 Google 連携</a></li>
                    <li><a href="/admin/users" class="{{ request()->is('admin/users*') ? 'active' : '' }}">👥 ユーザー管理</a></li>
                    @endif
                </ul>
            </li>
            <li><a href="/manual.html" target="_blank">📖 使い方</a></li>
        </ul>
        <div class="navbar-right">
            <span class="user-name">{{ Auth::user()->name ?? '' }}</span>
            @php
                // 運営管理切替ボタン表示判定:
                //   - 既に super_admins テーブルに登録済み、または
                //   - .env の ADMIN_EMAIL と一致するメールアドレス（マスター管理者）
                $userEmail = Auth::user()->email ?? '';
                $isSuperAdminEligible = $userEmail && (
                    \App\Models\SuperAdmin::where('email', $userEmail)->exists()
                    || $userEmail === env('ADMIN_EMAIL')
                );
            @endphp
            @if($isSuperAdminEligible)
            <form method="POST" action="/super-admin/switch-from-admin" style="display:inline;">
                @csrf
                <button type="submit" class="logout-btn" style="background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);margin-right:8px;" title="運営管理画面に切り替え">
                    🛡️ 運営管理
                </button>
            </form>
            @endif
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
        @if($errors->any())
            <div class="alert alert-error">
                <ul style="margin:0;padding-left:18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
