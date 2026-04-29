<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * 単一DB環境(ロリポップ等)で master 用テーブル(super_admins, tenants,
 * invoices, invoice_items)を default DB に同居させて作成するコマンド。
 *
 *   php artisan master:bootstrap                       # テーブルだけ作成
 *   php artisan master:bootstrap --admin-email=foo@bar.com   # 同時に super_admin 作成
 */
class MasterBootstrap extends Command
{
    protected $signature = 'master:bootstrap
        {--admin-email= : super_admin として登録するメールアドレス}
        {--admin-name=Super Admin : 表示名}
        {--admin-password= : パスワード(未指定時はランダム生成して表示)}';

    protected $description = '単一DB運用向けに master 接続のテーブル(super_admins/tenants/invoices/invoice_items)を作成';

    public function handle(): int
    {
        $conn = 'master';
        $dbName = config("database.connections.$conn.database");
        $this->info("master 接続のDB: {$dbName}");

        $schema = Schema::connection($conn);

        if (!$schema->hasTable('tenants')) {
            $schema->create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('company_name');
                $table->string('subdomain')->unique();
                $table->string('db_name')->unique();
                $table->string('db_username')->nullable();
                $table->string('db_password')->nullable();
                $table->string('plan')->default('standard');
                $table->unsignedInteger('monthly_fee_per_store')->default(11000);
                $table->unsignedInteger('monthly_fee_override')->nullable();
                $table->string('billing_company_name')->nullable();
                $table->string('billing_postal_code', 10)->nullable();
                $table->text('billing_address')->nullable();
                $table->string('contact_email');
                $table->string('contact_name')->nullable();
                $table->integer('ai_monthly_limit')->default(200);
                $table->boolean('is_active')->default(true);
                $table->date('contract_start')->nullable();
                $table->date('contract_end')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
            $this->info('tenants テーブルを作成しました');
        } else {
            $this->line('tenants テーブルは既に存在します');
        }

        if (!$schema->hasTable('super_admins')) {
            $schema->create('super_admins', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
            $this->info('super_admins テーブルを作成しました');
        } else {
            $this->line('super_admins テーブルは既に存在します');
        }

        if (!$schema->hasTable('invoices')) {
            $schema->create('invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('invoice_number', 30)->unique();
                $table->date('billing_period_start');
                $table->date('billing_period_end');
                $table->date('issue_date');
                $table->date('due_date');
                $table->unsignedInteger('subtotal');
                $table->decimal('tax_rate', 5, 2)->default(10.00);
                $table->unsignedInteger('tax_amount');
                $table->unsignedInteger('total_amount');
                $table->string('status', 20)->default('draft');
                $table->date('paid_date')->nullable();
                $table->text('notes')->nullable();
                $table->text('billing_company_name_snapshot')->nullable();
                $table->text('billing_address_snapshot')->nullable();
                $table->text('billing_postal_code_snapshot')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'billing_period_start']);
            });
            $this->info('invoices テーブルを作成しました');
        } else {
            $this->line('invoices テーブルは既に存在します');
        }

        if (!$schema->hasTable('invoice_items')) {
            $schema->create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id')->index();
                $table->string('description', 255);
                $table->unsignedInteger('quantity')->default(1);
                $table->unsignedInteger('unit_price');
                $table->unsignedInteger('amount');
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
            $this->info('invoice_items テーブルを作成しました');
        } else {
            $this->line('invoice_items テーブルは既に存在します');
        }

        $email = $this->option('admin-email');
        if ($email) {
            $exists = DB::connection($conn)->table('super_admins')->where('email', $email)->exists();
            if ($exists) {
                $this->line("super_admin {$email} は既に存在します");
            } else {
                $password = $this->option('admin-password') ?: bin2hex(random_bytes(6));
                DB::connection($conn)->table('super_admins')->insert([
                    'name'       => $this->option('admin-name'),
                    'email'      => $email,
                    'password'   => Hash::make($password),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("super_admin を作成しました: {$email} / {$password}");
            }
        }

        $this->info('完了。/super-admin/login からログイン、または admin ログイン後に「🛡️ 運営管理」ボタンで自動切替できます。');
        return self::SUCCESS;
    }
}
