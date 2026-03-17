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
Route::get('/review/{slug}', [\App\Http\Controllers\ReviewController::class, 'show']);
Route::post('/review/{slug}/confirm', [\App\Http\Controllers\ReviewController::class, 'confirm']);
Route::post('/review/{slug}', [\App\Http\Controllers\ReviewController::class, 'store']);
Route::post('/review/{slug}/suggest', [\App\Http\Controllers\ReviewController::class, 'suggest']);
Route::get('/review/{slug}/thankyou', [\App\Http\Controllers\ReviewController::class, 'thankyou']);


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

    // 店舗管理
    Route::get('/stores', [\App\Http\Controllers\Admin\StoreController::class, 'index']);
    Route::get('/stores/create', [\App\Http\Controllers\Admin\StoreController::class, 'create']);
    Route::post('/stores', [\App\Http\Controllers\Admin\StoreController::class, 'store']);
    Route::get('/stores/{store}/edit', [\App\Http\Controllers\Admin\StoreController::class, 'edit']);
    Route::put('/stores/{store}', [\App\Http\Controllers\Admin\StoreController::class, 'update']);

    // QRコード
    Route::get('/stores/{store}/qrcode', [\App\Http\Controllers\Admin\QrCodeController::class, 'show']);
    Route::get('/stores/{store}/qrcode/download', [\App\Http\Controllers\Admin\QrCodeController::class, 'download']);

    // 口コミ一覧
    Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index']);
    Route::get('/reviews/export', [\App\Http\Controllers\Admin\ReviewController::class, 'export']);

    // 口コミテーマ管理
    Route::get('/suggestion-themes', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'index']);
    Route::put('/suggestion-themes/display-count', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'updateDisplayCount']);
    Route::post('/suggestion-themes/categories', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'storeCategory']);
    Route::put('/suggestion-themes/categories/{category}', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'updateCategory']);
    Route::delete('/suggestion-themes/categories/{category}', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'destroyCategory']);
    Route::post('/suggestion-themes/themes', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'storeTheme']);
    Route::put('/suggestion-themes/themes/{theme}', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'updateTheme']);
    Route::delete('/suggestion-themes/themes/{theme}', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'destroyTheme']);

    // Google口コミ管理
    Route::get('/google-reviews', [\App\Http\Controllers\Admin\GoogleReviewController::class, 'index']);
    Route::post('/google-reviews/sync', [\App\Http\Controllers\Admin\GoogleReviewController::class, 'sync']);
    Route::post('/google-reviews/generate-reply', [\App\Http\Controllers\Admin\GoogleReviewController::class, 'generateReply']);
    Route::post('/google-reviews/{review}/reply', [\App\Http\Controllers\Admin\GoogleReviewController::class, 'reply']);
    Route::delete('/google-reviews/{review}/reply', [\App\Http\Controllers\Admin\GoogleReviewController::class, 'deleteReply']);

    // 返信カテゴリ・キーワード管理
    Route::get('/reply-categories', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'index']);
    Route::post('/reply-categories/categories', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'storeCategory']);
    Route::put('/reply-categories/categories/{category}', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'updateCategory']);
    Route::delete('/reply-categories/categories/{category}', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'destroyCategory']);
    Route::post('/reply-categories/keywords', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'storeKeyword']);
    Route::put('/reply-categories/keywords/{keyword}', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'updateKeyword']);
    Route::delete('/reply-categories/keywords/{keyword}', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'destroyKeyword']);

    // Google連携設定
    Route::get('/google-settings', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'index']);
    Route::post('/google-settings/credentials', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'saveCredentials']);
    Route::get('/google-settings/authorize', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'redirectToGoogle']);
    Route::get('/google-settings/callback', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'callback']);
    Route::post('/google-settings/account', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'saveAccount']);
    Route::post('/google-settings/location-mapping', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'saveLocationMapping']);
    Route::post('/google-settings/disconnect', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'disconnect']);

    // 管理者専用：ユーザー管理 + 削除系操作
    Route::middleware('admin')->group(function () {
        Route::delete('/stores/{store}', [\App\Http\Controllers\Admin\StoreController::class, 'destroy']);
        Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index']);
        Route::get('/users/create', [\App\Http\Controllers\Admin\UserController::class, 'create']);
        Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store']);
        Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit']);
        Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update']);
        Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy']);
        Route::delete('/reviews/{review}', [\App\Http\Controllers\Admin\ReviewController::class, 'destroy']);
    });
});
