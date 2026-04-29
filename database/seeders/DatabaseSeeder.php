<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * 全テナント共通で流す汎用シーダー。
     *
     * ===========================================
     * 会社固有のセットアップは個別に実行:
     * ===========================================
     *
     *   【質屋アシスト (全部まとめて)】
     *     php artisan db:seed --class=ShichiAssistTenantSeeder
     *     → ShichiAssistSeeder + DefaultHashtagsSeeder + ShichiAssistIntegrationsSeeder
     *
     *   【本番デプロイ時 (既存データあり、店舗は増やさない)】
     *     php artisan db:seed --class=ShichiAssistIntegrationsSeeder
     *     → SiteSetting / .env を store_integrations に移行するだけ
     *
     *   【とん球 (焼肉店・デモ/テスト用)】
     *     php artisan db:seed --class=TonkyuSeeder
     */
    public function run()
    {
        $this->call([
            AdminSeeder::class,
            BusinessTypeSeeder::class,
            SuggestionThemeSeeder::class,
        ]);
    }
}
