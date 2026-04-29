<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantMigrate extends Command
{
    protected $signature = 'tenant:migrate
                            {subdomain? : テナントのサブドメイン（省略で全テナント）}
                            {--seed : 初期データもシードする}
                            {--fresh : テーブルを全削除してから再作成}
                            {--admin-email= : 初期管理者メールアドレス}
                            {--admin-password= : 初期管理者パスワード}';

    protected $description = 'テナントDBにマイグレーションを実行';

    public function handle(): int
    {
        $subdomain = $this->argument('subdomain');

        if ($subdomain) {
            $tenant = Tenant::where('subdomain', $subdomain)->first();
            if (!$tenant) {
                $this->error("テナント '{$subdomain}' が見つかりません。");
                return 1;
            }
            $tenants = collect([$tenant]);
        } else {
            $tenants = Tenant::where('is_active', true)->get();
            if ($tenants->isEmpty()) {
                $this->info('有効なテナントがありません。');
                return 0;
            }
        }

        foreach ($tenants as $tenant) {
            $this->info("▶ {$tenant->company_name} ({$tenant->subdomain}) のDBにマイグレーション実行中...");

            // テナントのDB接続を設定
            Config::set('database.connections.mysql.database', $tenant->db_name);
            Config::set('database.connections.mysql.username', $tenant->db_username ?: config('database.connections.master.username'));
            Config::set('database.connections.mysql.password', $tenant->db_password ?: config('database.connections.master.password'));
            DB::purge('mysql');
            DB::reconnect('mysql');

            try {
                // マイグレーション実行
                $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';
                Artisan::call($command, [
                    '--database' => 'mysql',
                    '--force' => true,
                    '--path' => 'database/migrations',
                ]);
                $this->info(Artisan::output());

                // シード実行
                if ($this->option('seed')) {
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
                    $this->info('  ✓ 初期シードデータを投入しました');
                }

                // 初期管理者作成
                $email = $this->option('admin-email') ?: $tenant->contact_email;
                $password = $this->option('admin-password') ?: 'password';

                $existing = DB::connection('mysql')->table('users')->where('email', $email)->exists();
                if (!$existing) {
                    DB::connection('mysql')->table('users')->insert([
                        'name' => $tenant->contact_name ?: $tenant->company_name . ' 管理者',
                        'email' => $email,
                        'password' => Hash::make($password),
                        'role' => 'admin',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->info("  ✓ 管理者アカウント作成: {$email}");
                }

                $this->info("  ✅ {$tenant->company_name} 完了\n");
            } catch (\Exception $e) {
                $this->error("  ❌ エラー: {$e->getMessage()}\n");
            }
        }

        return 0;
    }
}
