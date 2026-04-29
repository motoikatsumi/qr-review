<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $showTrashed = $request->input('show') === 'trashed';
        $query = $showTrashed ? User::onlyTrashed() : User::query();
        $users = $query->with('store')->orderBy('created_at', 'desc')->get();
        $trashedCount = User::onlyTrashed()->count();
        return view('admin.users.index', compact('users', 'showTrashed', 'trashedCount'));
    }

    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();
        return back()->with('success', "ユーザー「{$user->name}」を復元しました。");
    }

    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        if ($user->id === auth()->id()) {
            return back()->with('error', '自分自身は完全削除できません。');
        }
        $name = $user->name;
        $user->forceDelete();
        return back()->with('success', "ユーザー「{$name}」を完全に削除しました。");
    }

    public function create()
    {
        $stores = Store::orderBy('name')->get();
        return view('admin.users.create', compact('stores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(['admin', 'member', 'store_owner'])],
            'store_id' => 'nullable|exists:stores,id|required_if:role,store_owner',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'store_id' => $request->role === 'store_owner' ? $request->store_id : null,
        ]);

        return redirect('/admin/users')->with('success', 'ユーザーを追加しました。');
    }

    public function edit(User $user)
    {
        $stores = Store::orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'stores'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'member', 'store_owner'])],
            'store_id' => 'nullable|exists:stores,id|required_if:role,store_owner',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->store_id = $request->role === 'store_owner' ? $request->store_id : null;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect('/admin/users')->with('success', 'ユーザー情報を更新しました。');
    }

    public function destroy(User $user)
    {
        // 自分自身は削除不可
        if ($user->id === auth()->id()) {
            return redirect('/admin/users')->with('error', '自分自身は削除できません。');
        }

        // 最後の管理者は削除不可
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return redirect('/admin/users')->with('error', '最後の管理者は削除できません。');
        }

        $user->delete();

        return redirect('/admin/users')->with('success', 'ユーザーを削除しました。');
    }
}
