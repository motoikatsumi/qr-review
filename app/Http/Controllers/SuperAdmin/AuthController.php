<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('super_admin')->check()) {
            return redirect('/super-admin/tenants');
        }
        return view('super-admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('super_admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect('/super-admin/dashboard');
        }

        return back()->withErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。']);
    }

    public function logout(Request $request)
    {
        Auth::guard('super_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/super-admin/login');
    }

    /**
     * 通常の admin から運営管理へ自動切替
     * 同じメールアドレスが運営管理者として登録されていれば自動ログイン
     * 未登録の場合はその場で登録（admin として認証済みなので安全）
     */
    public function switchFromAdmin(Request $request)
    {
        $adminUser = Auth::guard('web')->user();
        if (!$adminUser) {
            return redirect('/admin/login')->with('error', 'admin としてログインしてください。');
        }

        // 既存の super_admin レコードを検索
        $superAdmin = \App\Models\SuperAdmin::where('email', $adminUser->email)->first();

        if (!$superAdmin) {
            // 未登録 → その場で作成（admin として既に認証済みなので問題なし）
            // パスワードは admin のハッシュをそのままコピー（同一ユーザーなので）
            $superAdmin = \App\Models\SuperAdmin::create([
                'name' => $adminUser->name,
                'email' => $adminUser->email,
                'password' => $adminUser->password, // 既存ハッシュをコピー
            ]);
        }

        Auth::guard('super_admin')->loginUsingId($superAdmin->id);
        return redirect('/super-admin/tenants')->with('success', '運営管理画面に切り替えました。');
    }

    public function showPasswordForm()
    {
        return view('super-admin.password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $admin = Auth::guard('super_admin')->user();

        if (!Hash::check($request->input('current_password'), $admin->password)) {
            return back()->withErrors(['current_password' => '現在のパスワードが正しくありません。']);
        }

        $admin->password = Hash::make($request->input('password'));
        $admin->save();

        return redirect('/super-admin/tenants')->with('success', 'パスワードを変更しました。');
    }
}
