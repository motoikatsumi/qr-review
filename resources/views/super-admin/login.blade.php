<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>運営管理 ログイン - QRレビュー</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Hiragino Sans', 'Noto Sans JP', sans-serif;
            background: linear-gradient(135deg, #7c2d12 0%, #c2410c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            width: 400px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }
        .login-card h1 {
            text-align: center;
            margin-bottom: 24px;
            font-size: 1.3rem;
            color: #7c2d12;
        }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 4px; font-weight: 500; font-size: 0.9rem; }
        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #c2410c;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-login:hover { background: #9a3412; }
        .error { color: #dc2626; font-size: 0.85rem; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>🛡️ 運営管理</h1>

        @if($errors->any())
            <div class="error">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ url('/super-admin/login') }}">
            @csrf
            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">ログイン</button>
        </form>
    </div>
</body>
</html>
