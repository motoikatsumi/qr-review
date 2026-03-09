<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// トップページ（/）にアクセスした場合は管理画面ログインへリダイレクト
Route::middleware('ip.restrict')->get('/', function () {
    return redirect()->route('login');
});

// ============================================================
// 顧客向け口コミページ（IP制限なし）
// ============================================================
// デバッグ用：IP確認（原因特定後に削除）
Route::get('/debug-ip', function (\Illuminate\Http\Request $request) {
    $allowedIps = array_filter(array_map('trim', explode(',', env('ALLOWED_IPS', ''))));
    $clientIp = $request->ip();
    return response()->json([
        'client_ip' => $clientIp,
        'is_allowed' => in_array($clientIp, $allowedIps),
        'allowed_ips' => $allowedIps,
        'env_raw' => env('ALLOWED_IPS', '(not set)'),
    ]);
});

Route::get('/review/{slug}', [\App\Http\Controllers\ReviewController::class, 'show']);
Route::post('/review/{slug}/confirm', [\App\Http\Controllers\ReviewController::class, 'confirm']);
Route::post('/review/{slug}', [\App\Http\Controllers\ReviewController::class, 'store']);
Route::post('/review/{slug}/suggest', [\App\Http\Controllers\ReviewController::class, 'suggest']);


// ============================================================
// 管理画面 認証（IP制限あり）
// ============================================================
Route::middleware('ip.restrict')->group(function () {
    Route::get('/admin/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/admin/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
    Route::post('/admin/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout']);
});

// ============================================================
// 管理画面（認証必須 + IP制限あり）
// ============================================================
Route::middleware(['ip.restrict', 'auth'])->prefix('admin')->group(function () {
    // ダッシュボード（統計）
    Route::get('/', function () {
        return redirect('/admin/dashboard');
    });
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index']);

    // 店舗管理 CRUD
    Route::get('/stores', [\App\Http\Controllers\Admin\StoreController::class, 'index']);
    Route::get('/stores/create', [\App\Http\Controllers\Admin\StoreController::class, 'create']);
    Route::post('/stores', [\App\Http\Controllers\Admin\StoreController::class, 'store']);
    Route::get('/stores/{store}/edit', [\App\Http\Controllers\Admin\StoreController::class, 'edit']);
    Route::put('/stores/{store}', [\App\Http\Controllers\Admin\StoreController::class, 'update']);
    Route::delete('/stores/{store}', [\App\Http\Controllers\Admin\StoreController::class, 'destroy']);

    // QRコード
    Route::get('/stores/{store}/qrcode', [\App\Http\Controllers\Admin\QrCodeController::class, 'show']);
    Route::get('/stores/{store}/qrcode/download', [\App\Http\Controllers\Admin\QrCodeController::class, 'download']);

    // 口コミ一覧
    Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index']);
    Route::get('/reviews/export', [\App\Http\Controllers\Admin\ReviewController::class, 'export']);
});
