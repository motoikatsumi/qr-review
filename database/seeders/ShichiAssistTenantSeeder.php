<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 質屋アシスト テナント用 一括セットアップシーダー。
 *
 * 用途:
 *   - 質屋アシストの環境を新規構築する時に一発でデータ投入
 *   - 本番デプロイ後の初回セットアップ
 *   - 別環境（ステージング等）で質屋アシストを再現したい時
 *
 * 実行:
 *   php artisan db:seed --class=ShichiAssistTenantSeeder
 *
 * 前提:
 *   - 事前に php artisan migrate 済み
 *   - 事前に DatabaseSeeder（共通）実行済み（BusinessType 等が入っている）
 *   - 本番に既存データがある場合は ShichiAssistIntegrationsSeeder だけを個別実行
 */
class ShichiAssistTenantSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('=== 質屋アシスト テナント セットアップ開始 ===');

        // 1. 5 店舗 + 投稿テンプレート
        $this->command->info('--- ShichiAssistSeeder (5 店舗) ---');
        $this->call(ShichiAssistSeeder::class);

        // 2. 業種・店舗ごとのデフォルトハッシュタグ
        $this->command->info('--- DefaultHashtagsSeeder (ハッシュタグ) ---');
        $this->call(DefaultHashtagsSeeder::class);

        // 3. 既存 SiteSetting / .env から store_integrations への移行
        //    本番以外の新規環境では、ここで作成される integration は空（SiteSetting が無いため）
        $this->command->info('--- ShichiAssistIntegrationsSeeder (連携移行) ---');
        $this->call(ShichiAssistIntegrationsSeeder::class);

        $this->command->info('=== 質屋アシスト テナント セットアップ完了 ===');
    }
}
