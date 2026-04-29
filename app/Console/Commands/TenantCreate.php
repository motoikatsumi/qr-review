<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * 新規テナント追加コマンド
 *
 * 前提: ロリポップ管理画面で以下を済ませておく
 *   1. サブドメイン（例: kakaku.review.assist-grp.net）作成
 *   2. データベース（例: LAA1386365-kakaku）作成
 *
 * 使い方:
 *   php artisan tenant:create
 *     → 対話式で必要事項を入力
 *
 *   php artisan tenant:create \
 *     --subdomain=kakaku \
 *     --company="価格.com 株式会社" \
 *     --db=LAA1386365-kakaku \
 *     --email=admin@kakaku.com \
 *     --password=initial-password
 */
class TenantCreate extends Command
{
    protected $signature = 'tenant:create
        {--subdomain= : サブドメイン（必須、例: kakaku）}
        {--company= : 会社名（必須）}
        {--db= : DB 名（必須、ロリポップで作成済みのもの）}
        {--db-user= : DB ユーザー名（省略時は .env の DB_USERNAME）}
        {--db-password= : DB パスワード（省略時は .env の DB_PASSWORD）}
        {--email= : 管理者メールアドレス（必須）}
        {--password= : 管理者パスワード（省略時はランダム生成）}
        {--name= : 管理者氏名（省略時は会社名 + 管理者）}
        {--plan=standard : プラン（light/standard/premium）}
        {--no-seed : 初期シードデータを投入しない}';

    protected $description = '新規テナントを追加（master DB に登録 + テナント DB マイグレーション + 初期管理者作成）';

    public function handle(): int
    {
        // 1. 入力受付
        $subdomain = $this->option('subdomain') ?: $this->ask('サブドメイン（例: kakaku）');
        $company   = $this->option('company')   ?: $this->ask('会社名');
        $dbName    = $this->option('db')        ?: $this->ask('ロリポップで作成した DB 名');
        $email     = $this->option('email')     ?: $this->ask('管理者メールアドレス');
        $name      = $this->option('name')      ?: $company . ' 管理者';
        $plan      = $this->option('plan');
        $dbUser    = $this->option('db-user') ?: env('DB_USERNAME');
        $dbPass    = $this->option('db-password') ?: env('DB_PASSWORD');
        $password  = $this->option('password') ?: \Illuminate\Support\Str::random(16);

        // バリデーション
        if (!$subdomain || !$company || !$dbName || !$email) {
            $this->error('必須項目が不足しています');
            return 1;
        }

        // 2. 重複チェック
        $existing = Tenant::where('subdomain', $subdomain)->first();
        if ($existing) {
            $this->error("サブドメイン '{$subdomain}' は既に登録されています（会社名: {$existing->company_name}）");
            return 1;
        }

        // 3. DB 接続テスト（ロリポップで DB がちゃんと作られているか）
        $this->info("▶ DB 接続テスト: {$dbName}");
        try {
            Config::set('database.connections.mysql.database', $dbName);
            Config::set('database.connections.mysql.username', $dbUser);
            Config::set('database.connections.mysql.password', $dbPass);
            DB::purge('mysql');
            DB::reconnect('mysql');
            DB::select('SELECT 1');
            $this->info('  ✅ DB 接続 OK');
        } catch (\Throwable $e) {
            $this->error("  ❌ DB に接続できません: {$e->getMessage()}");
            $this->error("  → ロリポップ管理画面で DB '{$dbName}' を作成し、ユーザー/パスワードを確認してください");
            return 1;
        }

        // 4. 確認
        $this->newLine();
        $this->info('==========================================');
        $this->info('以下の内容で新規テナントを作成します:');
        $this->info('==========================================');
        $this->table([], [
            ['会社名', $company],
            ['サブドメイン', $subdomain],
            ['DB 名', $dbName],
            ['プラン', $plan],
            ['管理者メール', $email],
            ['管理者パスワード', $password . '  ← メモしてください！'],
            ['管理者氏名', $name],
        ]);
        if (!$this->option('no-interaction') && !$this->confirm('この内容で作成しますか？', true)) {
            $this->warn('キャンセルしました');
            return 1;
        }

        // 5. master DB に登録
        $this->info('▶ master DB に tenants レコードを追加');
        $tenant = Tenant::create([
            'company_name'   => $company,
            'subdomain'      => $subdomain,
            'db_name'        => $dbName,
            'db_username'    => $dbUser !== env('DB_USERNAME') ? $dbUser : null,
            'db_password'    => $dbPass !== env('DB_PASSWORD') ? $dbPass : null,
            'plan'           => $plan,
            'contact_email'  => $email,
            'contact_name'   => $name,
            'is_active'      => true,
            'contract_start' => now()->toDateString(),
        ]);
        $this->info("  ✅ tenants.id = {$tenant->id}");

        // 6. テナント DB にマイグレーション
        $this->info('▶ テナント DB にマイグレーション実行');
        try {
            Artisan::call('migrate', [
                '--database' => 'mysql',
                '--force' => true,
                '--path' => 'database/migrations',
            ]);
            $this->info('  ✅ マイグレーション完了');
        } catch (\Throwable $e) {
            $this->error("  ❌ マイグレーション失敗: {$e->getMessage()}");
            $tenant->delete();
            return 1;
        }

        // 7. シード（業種マスタ・口コミテーマ）
        if (!$this->option('no-seed')) {
            $this->info('▶ 初期シードデータ投入');
            try {
                Artisan::call('db:seed', [
                    '--class' => 'BusinessTypeSeeder',
                    '--database' => 'mysql',
                    '--force' => true,
                ]);
                Artisan::call('db:seed', [
                    '--class' => 'SuggestionThemeSeeder',
                    '--database' => 'mysql',
                    '--force' => true,
                ]);
                $this->info('  ✅ 業種マスタ・口コミテーマを投入');
            } catch (\Throwable $e) {
                $this->warn("  ⚠️  シード失敗（手動で実行してください）: {$e->getMessage()}");
            }
        }

        // 8. 初期管理者作成
        $this->info('▶ 初期管理者アカウント作成');
        DB::connection('mysql')->table('users')->insert([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->info("  ✅ {$email} / {$password}");

        // 9. 完了報告
        $this->newLine();
        $this->info('🎉 テナント作成完了');
        $this->info('==========================================');
        $this->info("📍 顧客アクセス URL: https://{$subdomain}.review.assist-grp.net/admin/login");
        $this->info("📧 ログインメール: {$email}");
        $this->info("🔑 ログインパスワード: {$password}");
        $this->info("⚠️  パスワードを顧客に共有後、すぐに変更してもらってください");
        $this->info('==========================================');

        return 0;
    }
}
