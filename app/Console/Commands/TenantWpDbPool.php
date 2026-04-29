<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 自動 WordPress 構築用の DB プールを管理する CLI
 *
 * 使い方:
 *   php artisan tenant-wp:pool list
 *   php artisan tenant-wp:pool add wp_pool_01 wp_pool_02 wp_pool_03
 *   php artisan tenant-wp:pool remove wp_pool_01
 */
class TenantWpDbPool extends Command
{
    protected $signature = 'tenant-wp:pool {action : list|add|remove} {names?* : DB 名（複数指定可）}';

    protected $description = '自動 WordPress 構築用の DB プールを管理（ロリポップ等の共有レンタルサーバー向け）';

    public function handle(): int
    {
        $action = $this->argument('action');
        $names = $this->argument('names') ?? [];

        switch ($action) {
            case 'list':
                $this->listPool();
                return 0;

            case 'add':
                if (empty($names)) {
                    $this->error('DB 名を 1 つ以上指定してください');
                    return 1;
                }
                $this->addToPool($names);
                return 0;

            case 'remove':
                if (empty($names)) {
                    $this->error('DB 名を指定してください');
                    return 1;
                }
                $this->removeFromPool($names);
                return 0;

            default:
                $this->error("不明なアクション: {$action}（list / add / remove）");
                return 1;
        }
    }

    private function listPool(): void
    {
        $rows = DB::table('tenant_wp_db_pool')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            $this->info('プールに DB が登録されていません');
            return;
        }
        $this->table(
            ['ID', 'DB 名', '状態', '割当店舗 ID', '割当日時'],
            $rows->map(fn($r) => [
                $r->id,
                $r->db_name,
                $r->store_id ? '🔴 使用中' : '🟢 空き',
                $r->store_id ?? '-',
                $r->assigned_at ?? '-',
            ])->toArray()
        );
        $available = $rows->whereNull('store_id')->count();
        $used = $rows->whereNotNull('store_id')->count();
        $this->info("空き: {$available} 件 / 使用中: {$used} 件 / 合計: " . $rows->count() . ' 件');
    }

    private function addToPool(array $names): void
    {
        foreach ($names as $name) {
            $exists = DB::table('tenant_wp_db_pool')->where('db_name', $name)->exists();
            if ($exists) {
                $this->warn("⏭  既に登録済み: {$name}");
                continue;
            }
            DB::table('tenant_wp_db_pool')->insert([
                'db_name' => $name,
                'store_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info("✅ 追加: {$name}");
        }
    }

    private function removeFromPool(array $names): void
    {
        foreach ($names as $name) {
            $row = DB::table('tenant_wp_db_pool')->where('db_name', $name)->first();
            if (!$row) {
                $this->warn("⏭  存在しません: {$name}");
                continue;
            }
            if ($row->store_id) {
                $this->error("❌ 使用中のため削除できません: {$name}（store_id={$row->store_id}）");
                continue;
            }
            DB::table('tenant_wp_db_pool')->where('id', $row->id)->delete();
            $this->info("🗑  削除: {$name}");
        }
    }
}
