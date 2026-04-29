<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SiteSetting;
use App\Models\Store;
use App\Models\StoreIntegration;
use Illuminate\Support\Facades\Log;

/**
 * 質屋アシスト 本番環境で SiteSetting / .env に保存されている
 * Instagram / Facebook / WordPress の認証情報を store_integrations テーブルに移行するシーダー。
 *
 * 実行タイミング:
 *   マルチテナント版デプロイ直後、質屋アシストテナントのDBで 1 回だけ実行:
 *     php artisan db:seed --class=ShichiAssistIntegrationsSeeder
 *
 * 冪等性: 同じ店舗・サービスの組み合わせは updateOrCreate で上書き。
 */
class ShichiAssistIntegrationsSeeder extends Seeder
{
    public function run()
    {
        // 質屋アシストの店舗（business_type=pawn を対象）
        $stores = Store::whereHas('businessType', function ($q) {
            $q->where('slug', 'pawn');
        })->get();

        if ($stores->isEmpty()) {
            $this->command->warn('質屋アシストの店舗が見つかりません。先に ShichiAssistSeeder を実行してください。');
            return;
        }

        // --------------------------------------------------------------
        // 1. Instagram / Facebook 連携
        //    本番では FB Graph API 経由で Instagram 投稿しているため、
        //    Instagram の access_token も Facebook Page Access Token と同じものを入れる。
        //    IG Business Account ID は instagram_user_id または
        //    instagram_business_account_id から取得。
        // --------------------------------------------------------------

        $fbToken    = SiteSetting::get('facebook_page_access_token');
        $fbPageId   = SiteSetting::get('facebook_page_id');
        $igBizId    = SiteSetting::get('instagram_business_account_id')
                   ?: SiteSetting::get('instagram_user_id');

        if ($fbToken && $fbPageId) {
            foreach ($stores as $store) {
                StoreIntegration::updateOrCreate(
                    ['store_id' => $store->id, 'service' => 'facebook'],
                    [
                        'access_token' => $fbToken,
                        'extra_data'   => ['page_id' => $fbPageId],
                        'is_active'    => true,
                    ]
                );
                $this->command->info("Facebook integration: store #{$store->id} ({$store->name})");
            }
        } else {
            $this->command->warn('facebook_page_access_token / facebook_page_id が未設定のため Facebook 連携はスキップ');
        }

        if ($fbToken && $igBizId) {
            foreach ($stores as $store) {
                StoreIntegration::updateOrCreate(
                    ['store_id' => $store->id, 'service' => 'instagram'],
                    [
                        // Instagram も FB Graph 経由で投稿するため、同じ FB Page Token を使用
                        'access_token' => $fbToken,
                        'extra_data'   => ['ig_user_id' => $igBizId],
                        'is_active'    => true,
                    ]
                );
                $this->command->info("Instagram integration: store #{$store->id} ({$store->name})");
            }
        } else {
            $this->command->warn('IG Business Account ID が未設定のため Instagram 連携はスキップ');
        }

        // --------------------------------------------------------------
        // 2. WordPress 連携
        //    現在は config('services.wordpress.*') 経由で .env から取得している。
        //    これを store_integrations に移す。
        // --------------------------------------------------------------

        $wpUrl      = config('services.wordpress.url', env('WORDPRESS_URL'));
        $wpUsername = config('services.wordpress.username', env('WORDPRESS_USERNAME'));
        $wpAppPass  = config('services.wordpress.app_password', env('WORDPRESS_APP_PASSWORD'));

        if ($wpUrl && $wpUsername && $wpAppPass) {
            foreach ($stores as $store) {
                StoreIntegration::updateOrCreate(
                    ['store_id' => $store->id, 'service' => 'wordpress'],
                    [
                        'access_token' => $wpAppPass,
                        'extra_data'   => [
                            'wp_url'      => $wpUrl,
                            'wp_username' => $wpUsername,
                        ],
                        'is_active'    => true,
                    ]
                );
                $this->command->info("WordPress integration: store #{$store->id} ({$store->name})");
            }
        } else {
            $this->command->warn('WordPress 設定 (.env の WORDPRESS_*) が未設定のためスキップ');
        }

        $this->command->info('質屋アシスト integrations の移行が完了しました（店舗数: ' . $stores->count() . '）');
    }
}
