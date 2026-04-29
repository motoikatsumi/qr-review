<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::orderBy('company_name')->get();

        // 各テナントのAI利用状況を取得
        foreach ($tenants as $tenant) {
            try {
                $tenant->ai_used_this_month = DB::connection('mysql')
                    ->table('ai_usage_logs')
                    ->tap(function () use ($tenant) {
                        // テナントDBに一時接続
                        config(['database.connections.tenant_check.driver' => 'mysql']);
                        config(['database.connections.tenant_check.host' => config('database.connections.mysql.host')]);
                        config(['database.connections.tenant_check.port' => config('database.connections.mysql.port')]);
                        config(['database.connections.tenant_check.database' => $tenant->db_name]);
                        config(['database.connections.tenant_check.username' => $tenant->db_username ?: config('database.connections.mysql.username')]);
                        config(['database.connections.tenant_check.password' => $tenant->db_password ?: config('database.connections.mysql.password')]);
                    });

                $tenant->ai_used_this_month = DB::connection('tenant_check')
                    ->table('ai_usage_logs')
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count();

                // 店舗情報を取得
                $stores = DB::connection('tenant_check')
                    ->table('stores')
                    ->select('name')
                    ->orderBy('id')
                    ->get();
                $tenant->store_count = $stores->count();
                $tenant->store_names = $stores->pluck('name')->toArray();

                DB::purge('tenant_check');
            } catch (\Exception $e) {
                $tenant->ai_used_this_month = '-';
                $tenant->store_count = '-';
                $tenant->store_names = [];
            }
        }

        return view('super-admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        return view('super-admin.tenants.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255',
            'subdomain'      => 'required|string|max:63|unique:master.tenants,subdomain|regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
            'db_name'        => 'required|string|max:255|unique:master.tenants,db_name',
            'db_username'    => 'nullable|string|max:255',
            'db_password'    => 'nullable|string|max:255',
            'plan'           => 'required|in:light,standard,pro',
            'monthly_fee_per_store' => 'nullable|integer|min:0',
            'monthly_fee_override' => 'nullable|integer|min:0',
            'billing_company_name' => 'nullable|string|max:255',
            'billing_postal_code' => 'nullable|string|max:10',
            'billing_address' => 'nullable|string|max:500',
            'contact_email'  => 'required|email|max:255',
            'contact_name'   => 'nullable|string|max:255',
            'ai_monthly_limit' => 'nullable|integer|min:0',
            'contract_start' => 'nullable|date',
            'contract_end'   => 'nullable|date',
            'notes'          => 'nullable|string',
            'admin_password' => 'nullable|string|min:8|max:100',
        ]);

        // プランに応じた AI 上限デフォルト
        if (empty($validated['ai_monthly_limit'])) {
            $limits = Tenant::planLimits();
            $validated['ai_monthly_limit'] = $limits[$validated['plan']] ?? 200;
        }

        // ============================================
        // STEP 1: テナント DB への接続テスト
        // ============================================
        $dbUser = $validated['db_username'] ?: env('DB_USERNAME');
        $dbPass = $validated['db_password'] ?: env('DB_PASSWORD');

        try {
            Config::set('database.connections.mysql.database', $validated['db_name']);
            Config::set('database.connections.mysql.username', $dbUser);
            Config::set('database.connections.mysql.password', $dbPass);
            DB::purge('mysql');
            DB::reconnect('mysql');
            DB::select('SELECT 1');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->withErrors([
                'db_name' => "データベース '{$validated['db_name']}' に接続できません。ロリポップ管理画面で DB が作成されているか確認してください。詳細: " . $e->getMessage(),
            ]);
        }

        // ============================================
        // STEP 2: master DB にテナントレコード作成
        // ============================================
        $adminPassword = $validated['admin_password'] ?? Str::random(16);
        $tenantData = collect($validated)->except('admin_password')->toArray();
        $tenant = Tenant::create($tenantData);

        // ============================================
        // STEP 3: テナント DB にマイグレーション実行
        // ============================================
        $errors = [];
        try {
            Artisan::call('migrate', [
                '--database' => 'mysql',
                '--force' => true,
                '--path' => 'database/migrations',
            ]);
        } catch (\Throwable $e) {
            $errors[] = 'マイグレーション失敗: ' . $e->getMessage();
            $tenant->delete();
            return redirect()->back()->withInput()->withErrors(['db_name' => implode("\n", $errors)]);
        }

        // ============================================
        // STEP 4: 業種マスタ・口コミテーマをシード
        // ============================================
        try {
            Artisan::call('db:seed', [
                '--class' => 'BusinessTypeSeeder',
                '--database' => 'mysql',
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            $errors[] = '業種マスタシード失敗: ' . $e->getMessage();
        }
        try {
            Artisan::call('db:seed', [
                '--class' => 'SuggestionThemeSeeder',
                '--database' => 'mysql',
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            $errors[] = '口コミテーマシード失敗: ' . $e->getMessage();
        }

        // ============================================
        // STEP 5: 初期管理者ユーザー作成
        // ============================================
        try {
            DB::connection('mysql')->table('users')->insert([
                'name' => $validated['contact_name'] ?: $validated['company_name'] . ' 管理者',
                'email' => $validated['contact_email'],
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $errors[] = '初期管理者作成失敗: ' . $e->getMessage();
        }

        // ============================================
        // 完了 → 結果画面（ログイン情報表示）
        // ============================================
        $loginUrl = $this->buildLoginUrl($validated['subdomain']);

        session()->flash('tenant_created', [
            'tenant' => $tenant,
            'login_url' => $loginUrl,
            'admin_email' => $validated['contact_email'],
            'admin_password' => $adminPassword,
            'errors' => $errors,
        ]);

        return redirect("/super-admin/tenants/{$tenant->id}/created");
    }

    /**
     * テナント追加完了画面（ログイン情報を 1 回だけ表示）
     */
    public function showCreated(Tenant $tenant)
    {
        $info = session('tenant_created');
        if (!$info || $info['tenant']->id !== $tenant->id) {
            return redirect('/super-admin/tenants')->with('warning', 'パスワード等の情報は 1 回しか表示できません。再表示はできないため、保存し忘れた場合はパスワードリセットを使ってください。');
        }
        return view('super-admin.tenants.created', $info);
    }

    private function buildLoginUrl(string $subdomain): string
    {
        $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?? 'review.assist-grp.net';
        // ローカル開発時は app.url のドメインそのままにフォールバック
        if (in_array($appHost, ['127.0.0.1', 'localhost'])) {
            return rtrim(config('app.url'), '/') . '/admin/login';
        }
        // サブドメイン部分を抜き出す（既にサブドメインがある場合は置換）
        $parts = explode('.', $appHost);
        if (count($parts) >= 3) {
            // 例: review.assist-grp.net → kakaku.assist-grp.net ではなく
            //     kakaku.review.assist-grp.net にしたいので、先頭に追加
            return "https://{$subdomain}." . $appHost . "/admin/login";
        }
        return "https://{$subdomain}." . $appHost . "/admin/login";
    }

    public function edit(Tenant $tenant)
    {
        return view('super-admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'company_name'   => 'required|string|max:255',
            'subdomain'      => 'required|string|max:63|regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/|unique:master.tenants,subdomain,' . $tenant->id,
            'db_name'        => 'required|string|max:255|unique:master.tenants,db_name,' . $tenant->id,
            'db_username'    => 'nullable|string|max:255',
            'db_password'    => 'nullable|string|max:255',
            'plan'           => 'required|in:light,standard,pro',
            'monthly_fee_per_store' => 'nullable|integer|min:0',
            'monthly_fee_override' => 'nullable|integer|min:0',
            'billing_company_name' => 'nullable|string|max:255',
            'billing_postal_code' => 'nullable|string|max:10',
            'billing_address' => 'nullable|string|max:500',
            'contact_email'  => 'required|email|max:255',
            'contact_name'   => 'nullable|string|max:255',
            'ai_monthly_limit' => 'nullable|integer|min:0',
            'is_active'      => 'boolean',
            'contract_start' => 'nullable|date',
            'contract_end'   => 'nullable|date',
            'notes'          => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $tenant->update($validated);

        return redirect('/super-admin/tenants')->with('success', 'テナント情報を更新しました。');
    }

    /**
     * テナント削除（マスターDBのレコードのみ削除。テナントDBは残す）
     */
    public function destroy(Request $request, Tenant $tenant)
    {
        $companyName = $tenant->company_name;
        $tenant->delete();

        return redirect('/super-admin/tenants')->with('success', $companyName . ' を削除しました。');
    }

    /**
     * 代理ログイン — テナントの管理ユーザーとしてログイン
     */
    public function impersonate(Tenant $tenant)
    {
        // テナントDBに接続
        Config::set('database.connections.mysql.database', $tenant->db_name);
        Config::set('database.connections.mysql.username', $tenant->db_username ?: env('DB_USERNAME'));
        Config::set('database.connections.mysql.password', $tenant->db_password ?: env('DB_PASSWORD'));
        DB::purge('mysql');
        DB::reconnect('mysql');

        // テナントDB内の最初の管理者ユーザーを取得
        $user = DB::connection('mysql')->table('users')->where('role', 'admin')->first();
        if (!$user) {
            $user = DB::connection('mysql')->table('users')->first();
        }

        if (!$user) {
            return redirect('/super-admin/tenants')->with('error', $tenant->company_name . ' にはユーザーが存在しません。');
        }

        // 運営管理セッションを保存（復帰用）
        session(['impersonating_from' => 'super_admin', 'impersonated_tenant_id' => $tenant->id]);

        // テナントのユーザーとしてログイン
        Auth::guard('web')->loginUsingId($user->id);

        return redirect('/admin/stores');
    }

    /**
     * AI利用ログ詳細
     */
    public function aiUsage(Tenant $tenant)
    {
        try {
            Config::set('database.connections.tenant_check.driver', 'mysql');
            Config::set('database.connections.tenant_check.host', config('database.connections.mysql.host'));
            Config::set('database.connections.tenant_check.port', config('database.connections.mysql.port'));
            Config::set('database.connections.tenant_check.database', $tenant->db_name);
            Config::set('database.connections.tenant_check.username', $tenant->db_username ?: config('database.connections.mysql.username'));
            Config::set('database.connections.tenant_check.password', $tenant->db_password ?: config('database.connections.mysql.password'));

            // 日別集計（過去30日）
            $dailyUsage = DB::connection('tenant_check')
                ->table('ai_usage_logs')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupByRaw('DATE(created_at)')
                ->orderBy('date')
                ->get();

            // アクション別集計（今月）
            $actionUsage = DB::connection('tenant_check')
                ->table('ai_usage_logs')
                ->selectRaw('action, COUNT(*) as count')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->groupBy('action')
                ->orderByDesc('count')
                ->get();

            // 最近のログ（50件）
            $recentLogs = DB::connection('tenant_check')
                ->table('ai_usage_logs')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            $monthlyTotal = DB::connection('tenant_check')
                ->table('ai_usage_logs')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();

            DB::purge('tenant_check');
        } catch (\Exception $e) {
            return redirect('/super-admin/tenants')->with('error', 'テナントDBへの接続に失敗しました: ' . $e->getMessage());
        }

        return view('super-admin.tenants.ai-usage', compact(
            'tenant', 'dailyUsage', 'actionUsage', 'recentLogs', 'monthlyTotal'
        ));
    }
}
