<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ============================================================
// 運営管理（テナント管理画面）
// ============================================================
Route::prefix('super-admin')->group(function () {
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/login', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'showLoginForm']);
        Route::post('/login', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'login']);
    });
    Route::post('/logout', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'logout']);

    // 通常 admin → 運営管理へ自動切替（admin auth 必須）
    Route::middleware('auth')->post('/switch-from-admin', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'switchFromAdmin']);

    Route::middleware('auth:super_admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\SuperAdmin\DashboardController::class, 'index']);
        Route::get('/password', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'showPasswordForm']);
        Route::put('/password', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'changePassword']);
        Route::get('/tenants', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'index']);
        Route::get('/tenants/create', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'create']);
        Route::post('/tenants', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'store']);
        Route::get('/tenants/{tenant}/created', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'showCreated']);
        Route::get('/tenants/{tenant}/edit', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'edit']);
        Route::put('/tenants/{tenant}', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'update']);
        Route::delete('/tenants/{tenant}', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'destroy']);
        Route::post('/tenants/{tenant}/impersonate', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'impersonate']);
        Route::get('/tenants/{tenant}/ai-usage', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'aiUsage']);

        // 請求書管理
        Route::get('/invoices', [\App\Http\Controllers\SuperAdmin\InvoiceController::class, 'index']);
        Route::get('/invoices/create', [\App\Http\Controllers\SuperAdmin\InvoiceController::class, 'create']);
        Route::post('/invoices', [\App\Http\Controllers\SuperAdmin\InvoiceController::class, 'store']);
        Route::get('/invoices/bulk', [\App\Http\Controllers\SuperAdmin\InvoiceController::class, 'bulkForm']);
        Route::post('/invoices/bulk', [\App\Http\Controllers\SuperAdmin\InvoiceController::class, 'bulkGenerate']);
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\SuperAdmin\InvoiceController::class, 'show']);
        Route::get('/invoices/{invoice}/print', [\App\Http\Controllers\SuperAdmin\InvoiceController::class, 'print']);
        Route::put('/invoices/{invoice}/status', [\App\Http\Controllers\SuperAdmin\InvoiceController::class, 'updateStatus']);
        Route::post('/invoices/bulk-paid', [\App\Http\Controllers\SuperAdmin\InvoiceController::class, 'bulkMarkPaid']);
        Route::delete('/invoices/{invoice}', [\App\Http\Controllers\SuperAdmin\InvoiceController::class, 'destroy']);
    });
});

// ============================================================
// Meta OAuth（Facebook/Instagram連携）— admin/store共用
// ============================================================
Route::middleware(['ip.restrict', 'auth'])->group(function () {
    Route::get('/meta/connect/{store}', [\App\Http\Controllers\MetaOAuthController::class, 'redirect']);
    Route::get('/meta/callback', [\App\Http\Controllers\MetaOAuthController::class, 'callback']);
    Route::get('/meta/select-page', [\App\Http\Controllers\MetaOAuthController::class, 'selectPage']);
    Route::post('/meta/save-page', [\App\Http\Controllers\MetaOAuthController::class, 'savePage']);
});

// Meta データ削除コールバック（Meta App 設定で要求される公開エンドポイント、認証なし）
Route::post('/meta/data-deletion-callback', [\App\Http\Controllers\MetaOAuthController::class, 'dataDeletionCallback']);
Route::get('/meta/data-deletion-status/{code}', [\App\Http\Controllers\MetaOAuthController::class, 'dataDeletionStatus']);

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
Route::post('/review/{slug}/upload-image', [\App\Http\Controllers\ReviewController::class, 'uploadImage']);
Route::delete('/review/{slug}/upload-image', [\App\Http\Controllers\ReviewController::class, 'deleteImage']);
Route::get('/review/{slug}/image/{filename}', [\App\Http\Controllers\ReviewController::class, 'serveImage'])
    ->where('filename', '[A-Za-z0-9_\-\.]+');
Route::get('/review/{slug}/thankyou', [\App\Http\Controllers\ReviewController::class, 'thankyou']);


// ============================================================
// 管理画面 認証（IP制限あり）
// GET（ログインフォーム表示）はレート制限なし、POST のみ制限する
// ============================================================
Route::middleware('ip.restrict')->group(function () {
    Route::get('/admin/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/admin/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout']);
    // 実際の認証試行のみレート制限（10 回 / 分・本番なら 5 にしても良い）
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/admin/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
    });
});

// ============================================================
// 管理画面（認証必須 + IP制限あり）
// ============================================================
Route::middleware(['ip.restrict', 'auth', 'redirect.store_owner'])->prefix('admin')->group(function () {
    // ダッシュボード（統計）
    Route::get('/', function () {
        return redirect('/admin/dashboard');
    });
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index']);

    // 業種管理
    Route::get('/business-types', [\App\Http\Controllers\Admin\BusinessTypeController::class, 'index']);
    Route::get('/business-types/create', [\App\Http\Controllers\Admin\BusinessTypeController::class, 'create']);
    Route::post('/business-types', [\App\Http\Controllers\Admin\BusinessTypeController::class, 'store']);
    Route::get('/business-types/{businessType}/edit', [\App\Http\Controllers\Admin\BusinessTypeController::class, 'edit']);
    Route::put('/business-types/{businessType}', [\App\Http\Controllers\Admin\BusinessTypeController::class, 'update']);
    Route::delete('/business-types/{businessType}', [\App\Http\Controllers\Admin\BusinessTypeController::class, 'destroy']);
    Route::post('/business-types/{id}/restore', [\App\Http\Controllers\Admin\BusinessTypeController::class, 'restore']);
    Route::delete('/business-types/{id}/force-delete', [\App\Http\Controllers\Admin\BusinessTypeController::class, 'forceDelete']);
    Route::post('/business-types/ai-suggest', [\App\Http\Controllers\Admin\BusinessTypeController::class, 'aiSuggest']);

    // 店舗管理（一覧閲覧は全テナント側ユーザー、追加・編集・削除は管理者のみ）
    Route::get('/stores', [\App\Http\Controllers\Admin\StoreController::class, 'index']);
    Route::middleware('admin')->group(function () {
        Route::get('/stores/create', [\App\Http\Controllers\Admin\StoreController::class, 'create']);
        Route::post('/stores', [\App\Http\Controllers\Admin\StoreController::class, 'store']);
        Route::get('/stores/{store}/edit', [\App\Http\Controllers\Admin\StoreController::class, 'edit']);
        Route::put('/stores/{store}', [\App\Http\Controllers\Admin\StoreController::class, 'update']);
        Route::post('/stores/{store}/duplicate', [\App\Http\Controllers\Admin\StoreController::class, 'duplicate']);
        Route::post('/stores/{id}/restore', [\App\Http\Controllers\Admin\StoreController::class, 'restore']);
        Route::delete('/stores/{id}/force-delete', [\App\Http\Controllers\Admin\StoreController::class, 'forceDelete']);
    });

    // 自動 WordPress セットアップ（FB/IG ブリッジ）
    Route::get('/stores/{store}/auto-wp/status', [\App\Http\Controllers\Admin\AutoWordPressController::class, 'status']);
    Route::post('/stores/{store}/auto-wp/install', [\App\Http\Controllers\Admin\AutoWordPressController::class, 'install']);
    Route::delete('/stores/{store}/auto-wp', [\App\Http\Controllers\Admin\AutoWordPressController::class, 'destroy']);
    Route::get('/stores/{store}/auto-wp/jetpack-status', [\App\Http\Controllers\Admin\AutoWordPressController::class, 'jetpackStatus']);
    Route::get('/stores/{store}/auto-wp/login-redirect', [\App\Http\Controllers\Admin\AutoWordPressController::class, 'loginRedirect']);

    // 店舗AI設定
    Route::get('/stores/{store}/ai-settings', [\App\Http\Controllers\Admin\StoreAiSettingController::class, 'edit']);
    Route::put('/stores/{store}/ai-settings', [\App\Http\Controllers\Admin\StoreAiSettingController::class, 'update']);
    Route::post('/stores/{store}/ai-settings/ai-suggest', [\App\Http\Controllers\Admin\StoreAiSettingController::class, 'aiSuggest']);

    // QRコード
    Route::get('/stores/{store}/qrcode', [\App\Http\Controllers\Admin\QrCodeController::class, 'show']);
    Route::get('/stores/{store}/qrcode/download', [\App\Http\Controllers\Admin\QrCodeController::class, 'download']);

    // 口コミ一覧
    Route::get('/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index']);
    Route::get('/reviews/export', [\App\Http\Controllers\Admin\ReviewController::class, 'export']);

    // 請求書（顧客閲覧用）
    Route::get('/invoices', [\App\Http\Controllers\Admin\InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'show']);
    Route::get('/invoices/{invoice}/print', [\App\Http\Controllers\Admin\InvoiceController::class, 'print']);

    // 口コミテーマ管理
    Route::get('/suggestion-themes', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'index']);
    Route::put('/suggestion-themes/display-count', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'updateDisplayCount']);
    Route::post('/suggestion-themes/categories', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'storeCategory']);
    Route::put('/suggestion-themes/categories/{category}', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'updateCategory']);
    Route::delete('/suggestion-themes/categories/{category}', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'destroyCategory']);
    Route::post('/suggestion-themes/themes', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'storeTheme']);
    Route::put('/suggestion-themes/themes/{theme}', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'updateTheme']);
    Route::delete('/suggestion-themes/themes/{theme}', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'destroyTheme']);
    Route::post('/suggestion-themes/categories/{id}/restore', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'restoreCategory']);
    Route::delete('/suggestion-themes/categories/{id}/force-delete', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'forceDeleteCategory']);
    Route::post('/suggestion-themes/themes/{id}/restore', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'restoreTheme']);
    Route::delete('/suggestion-themes/themes/{id}/force-delete', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'forceDeleteTheme']);
    Route::post('/suggestion-themes/ai-suggest', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'aiSuggest']);
    Route::post('/suggestion-themes/ai-apply', [\App\Http\Controllers\Admin\SuggestionThemeController::class, 'aiApply']);

    // Google口コミ管理
    Route::get('/google-reviews', [\App\Http\Controllers\Admin\GoogleReviewController::class, 'index']);
    Route::post('/google-reviews/sync', [\App\Http\Controllers\Admin\GoogleReviewController::class, 'sync']);
    Route::post('/google-reviews/generate-reply', [\App\Http\Controllers\Admin\GoogleReviewController::class, 'generateReply']);
    Route::post('/google-reviews/{review}/reply', [\App\Http\Controllers\Admin\GoogleReviewController::class, 'reply']);
    Route::post('/google-reviews/{review}/bulk-reply', [\App\Http\Controllers\Admin\GoogleReviewController::class, 'bulkReply']);
    Route::delete('/google-reviews/{review}/reply', [\App\Http\Controllers\Admin\GoogleReviewController::class, 'deleteReply']);

    // 返信カテゴリ・キーワード管理
    Route::get('/reply-categories', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'index']);
    Route::post('/reply-categories/categories', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'storeCategory']);
    Route::put('/reply-categories/categories/{category}', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'updateCategory']);
    Route::delete('/reply-categories/categories/{category}', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'destroyCategory']);
    Route::post('/reply-categories/keywords', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'storeKeyword']);
    Route::put('/reply-categories/keywords/{keyword}', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'updateKeyword']);
    Route::delete('/reply-categories/keywords/{keyword}', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'destroyKeyword']);
    Route::post('/reply-categories/categories/{id}/restore', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'restoreCategory']);
    Route::delete('/reply-categories/categories/{id}/force-delete', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'forceDeleteCategory']);
    Route::post('/reply-categories/keywords/{id}/restore', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'restoreKeyword']);
    Route::delete('/reply-categories/keywords/{id}/force-delete', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'forceDeleteKeyword']);
    Route::post('/reply-categories/ai-suggest', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'aiSuggest']);
    Route::post('/reply-categories/ai-apply', [\App\Http\Controllers\Admin\ReplyCategoryController::class, 'aiApply']);

    // 初期セットアップウィザード
    Route::get('/onboarding', [\App\Http\Controllers\Admin\OnboardingController::class, 'index']);
    Route::post('/onboarding/complete', [\App\Http\Controllers\Admin\OnboardingController::class, 'complete']);
    Route::post('/onboarding/skip', [\App\Http\Controllers\Admin\OnboardingController::class, 'skip']);
    Route::post('/onboarding/reset', [\App\Http\Controllers\Admin\OnboardingController::class, 'reset']);

    // AI 返信プレビュー＆フィードバック
    Route::get('/ai-reply-preview', [\App\Http\Controllers\Admin\AiReplyPreviewController::class, 'index']);
    Route::post('/ai-reply-preview/generate', [\App\Http\Controllers\Admin\AiReplyPreviewController::class, 'generate']);
    Route::post('/ai-reply-preview/feedback', [\App\Http\Controllers\Admin\AiReplyPreviewController::class, 'feedback']);
    Route::delete('/ai-reply-preview/feedback/{feedback}', [\App\Http\Controllers\Admin\AiReplyPreviewController::class, 'destroyFeedback']);

    // Google連携設定
    Route::get('/google-settings', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'index']);
    Route::post('/google-settings/credentials', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'saveCredentials']);
    Route::get('/google-settings/authorize', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'redirectToGoogle']);
    Route::get('/google-settings/callback', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'callback']);
    Route::post('/google-settings/account', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'saveAccount']);
    Route::post('/google-settings/test-connection', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'testConnection']);
    Route::post('/google-settings/location-mapping', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'saveLocationMapping']);
    Route::post('/google-settings/disconnect', [\App\Http\Controllers\Admin\GoogleSettingController::class, 'disconnect']);

    // 投稿管理
    Route::get('/purchase-posts', [\App\Http\Controllers\Admin\PurchasePostController::class, 'index'])->name('admin.purchase-posts.index');
    Route::get('/purchase-posts/create', [\App\Http\Controllers\Admin\PurchasePostController::class, 'create'])->name('admin.purchase-posts.create');
    Route::post('/purchase-posts/fetch-stock', [\App\Http\Controllers\Admin\PurchasePostController::class, 'fetchStock'])->name('admin.purchase-posts.fetch-stock');
    Route::post('/purchase-posts/generate-episode', [\App\Http\Controllers\Admin\PurchasePostController::class, 'generateEpisode'])->name('admin.purchase-posts.generate-episode');
    Route::post('/purchase-posts/generate-footer', [\App\Http\Controllers\Admin\PurchasePostController::class, 'generateFooter'])->name('admin.purchase-posts.generate-footer');
    Route::post('/purchase-posts', [\App\Http\Controllers\Admin\PurchasePostController::class, 'store'])->name('admin.purchase-posts.store');
    Route::get('/purchase-posts/{purchasePost}', [\App\Http\Controllers\Admin\PurchasePostController::class, 'show'])->name('admin.purchase-posts.show');
    Route::get('/purchase-posts/{purchasePost}/edit', [\App\Http\Controllers\Admin\PurchasePostController::class, 'edit'])->name('admin.purchase-posts.edit');
    Route::put('/purchase-posts/{purchasePost}', [\App\Http\Controllers\Admin\PurchasePostController::class, 'update'])->name('admin.purchase-posts.update');
    Route::post('/purchase-posts/{purchasePost}/retry', [\App\Http\Controllers\Admin\PurchasePostController::class, 'retry'])->name('admin.purchase-posts.retry');
    Route::delete('/purchase-posts/{purchasePost}', [\App\Http\Controllers\Admin\PurchasePostController::class, 'destroy'])->name('admin.purchase-posts.destroy');
    Route::post('/purchase-posts/{id}/restore', [\App\Http\Controllers\Admin\PurchasePostController::class, 'restore'])->name('admin.purchase-posts.restore');
    Route::delete('/purchase-posts/{id}/force-delete', [\App\Http\Controllers\Admin\PurchasePostController::class, 'forceDelete'])->name('admin.purchase-posts.force-delete');

    // 管理者専用：ユーザー管理 + 削除系操作
    Route::middleware('admin')->group(function () {
        Route::delete('/stores/{store}', [\App\Http\Controllers\Admin\StoreController::class, 'destroy']);
        Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index']);
        Route::get('/users/create', [\App\Http\Controllers\Admin\UserController::class, 'create']);
        Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store']);
        Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit']);
        Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update']);
        Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy']);
        Route::post('/users/{id}/restore', [\App\Http\Controllers\Admin\UserController::class, 'restore']);
        Route::delete('/users/{id}/force-delete', [\App\Http\Controllers\Admin\UserController::class, 'forceDelete']);
        Route::delete('/reviews/{review}', [\App\Http\Controllers\Admin\ReviewController::class, 'destroy']);
    });

    // 店舗外部連携設定（管理者が操作）— FB/IGはOAuth（/meta/connect）経由
    Route::get('/stores/{store}/integrations', [\App\Http\Controllers\Admin\StoreIntegrationController::class, 'index']);
    Route::delete('/stores/{store}/integrations/instagram/disconnect', [\App\Http\Controllers\Admin\StoreIntegrationController::class, 'destroy'])->defaults('service', 'instagram');
    Route::delete('/stores/{store}/integrations/facebook/disconnect', [\App\Http\Controllers\Admin\StoreIntegrationController::class, 'destroy'])->defaults('service', 'facebook');
    Route::post('/stores/{store}/integrations/wordpress', [\App\Http\Controllers\Admin\StoreIntegrationController::class, 'saveWordPress']);
    Route::delete('/stores/{store}/integrations/wordpress/disconnect', [\App\Http\Controllers\Admin\StoreIntegrationController::class, 'destroy'])->defaults('service', 'wordpress');
});

// ============================================================
// 店舗オーナー向けページ（認証必須 + IP制限あり）
// ============================================================
Route::middleware(['ip.restrict', 'auth', 'store.owner'])->prefix('store')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Store\DashboardController::class, 'index']);

    // QR口コミ
    Route::get('/reviews', [\App\Http\Controllers\Store\ReviewController::class, 'index']);

    // Google口コミ
    Route::get('/google-reviews', [\App\Http\Controllers\Store\GoogleReviewController::class, 'index']);
    Route::post('/google-reviews/generate-reply', [\App\Http\Controllers\Store\GoogleReviewController::class, 'generateReply']);
    Route::post('/google-reviews/{review}/reply', [\App\Http\Controllers\Store\GoogleReviewController::class, 'reply']);

    // 外部連携設定 — FB/IGはOAuth（/meta/connect）経由
    Route::get('/settings/integrations', [\App\Http\Controllers\Store\IntegrationController::class, 'index']);
    Route::delete('/settings/integrations/instagram/disconnect', [\App\Http\Controllers\Store\IntegrationController::class, 'destroy'])->defaults('service', 'instagram');
    Route::delete('/settings/integrations/facebook/disconnect', [\App\Http\Controllers\Store\IntegrationController::class, 'destroy'])->defaults('service', 'facebook');
    Route::post('/settings/integrations/wordpress', [\App\Http\Controllers\Store\IntegrationController::class, 'saveWordPress']);
    Route::delete('/settings/integrations/wordpress/disconnect', [\App\Http\Controllers\Store\IntegrationController::class, 'destroy'])->defaults('service', 'wordpress');

    // AI 設定
    Route::get('/settings/ai', [\App\Http\Controllers\Store\AiSettingController::class, 'edit']);
    Route::put('/settings/ai', [\App\Http\Controllers\Store\AiSettingController::class, 'update']);
    Route::post('/settings/ai/ai-suggest', [\App\Http\Controllers\Store\AiSettingController::class, 'aiSuggest']);

    // キーワード閲覧
    Route::get('/settings/keywords', [\App\Http\Controllers\Store\KeywordController::class, 'index']);
});
