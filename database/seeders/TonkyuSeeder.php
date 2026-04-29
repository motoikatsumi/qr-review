<?php

namespace Database\Seeders;

use App\Models\GoogleReview;
use App\Models\Store;
use Illuminate\Database\Seeder;

class TonkyuSeeder extends Seeder
{
    public function run()
    {
        // =========================================================
        // 店舗: とん球（焼肉店 / business_type_id = 2）
        // 〒890-0046 鹿児島県鹿児島市西田2丁目4-7
        // Google Maps: https://maps.app.goo.gl/QrsEMN4H4fDThFWN9
        // =========================================================
        $store = Store::updateOrCreate(
            ['slug' => 'tonkyu01'],
            [
                'name'                => 'とん球',
                'google_review_url'   => 'https://maps.app.goo.gl/QrsEMN4H4fDThFWN9',
                'ludocid'             => null,
                'google_location_name' => null,
                'meo_keywords'        => null,
                'meo_ratio'           => 30,
                'notify_email'        => 'motoi@assist-grp.jp',
                'notify_threshold'    => 3,
                'is_active'           => true,
                'business_type_id'    => 2,
                'ai_custom_instruction' => "鹿児島市西田にある七輪焼肉の韓国風居酒屋「とん球」です。\n七輪で焼くスタイルで、韓国直送のキムチが自慢です。\n「よだれシリーズ」が看板メニューで、豚バラも人気があります。\n水曜日はビール・ハイボール・レモンサワーが半額のハッピーアワーを実施しています。\nアットホームで気さくな雰囲気が特徴の、リーズナブルな焼肉店です。",
                'ai_extra_ng_words'   => "食べ放題\n激安\n他店\nチェーン店\n冷凍肉\n輸入肉",
                'ai_tone_preference'  => 'casual',
                'ai_area_keywords'    => "「鹿児島市西田」という地名を自然に混ぜる\n「鹿児島中央駅エリア」という地名を自然に混ぜる",
                'ai_service_keywords' => "七輪焼肉\n韓国風居酒屋\n韓国直送キムチ\nよだれシリーズ\n豚バラ\nハッピーアワー\nリーズナブル",
                'ai_reply_instruction' => "まずご来店への感謝を伝え、口コミで触れているメニュー（よだれシリーズ、キムチ、豚バラなど）に具体的にコメントしてください。\n七輪焼肉や韓国直送キムチへのこだわりを自然にPRしてください。\n水曜のハッピーアワーなど、次回来店につながる情報をさりげなく入れてください。\n気さくでアットホームな雰囲気が伝わるカジュアルなトーンでお願いします。\n低評価の場合は真摯に受け止め、改善を約束してください。",
                'ai_store_description' => '鹿児島市西田の七輪焼肉・韓国風居酒屋「とん球」のスタッフ',
                'custom_hashtags'     => "とん球\n七輪焼肉\n韓国風居酒屋\n鹿児島焼肉\n鹿児島市西田\n鹿児島中央駅\nよだれシリーズ\n韓国キムチ\nハッピーアワー",
                'ai_reply_length'     => 'short',
                'ai_suggestion_length' => 'medium',
            ]
        );

        // =========================================================
        // 実際のGoogle口コミデータ（2026年4月時点で取得）
        // =========================================================
        $reviews = [
            [
                'google_review_id' => 'tonkyu_review_001',
                'reviewer_name'    => '昭平',
                'rating'           => 5,
                'comment'          => '最近ハマってる焼肉屋さん。水曜日はビールとハイボール、レモンサワーが半額のハッピーアワーなのでお得です。とりま、焼肉が美味しい🤤',
                'reply_comment'    => null,
                'replied_at'       => null,
                'reviewed_at'      => now()->subMonths(8),
            ],
            [
                'google_review_id' => 'tonkyu_review_002',
                'reviewer_name'    => '安田孝裕',
                'rating'           => 5,
                'comment'          => '七輪で焼くタイプの韓国風居酒屋。店員さんが気さくで良い雰囲気のお店でした。韓国から取り寄せてるキムチが絶品でした。おすすめはよだれシリーズと豚バラです。1人あたりの料金 ￥3,000～4,000程度。',
                'reply_comment'    => null,
                'replied_at'       => null,
                'reviewed_at'      => now()->subMonths(8),
            ],
            [
                'google_review_id' => 'tonkyu_review_003',
                'reviewer_name'    => 'みーみーみー',
                'rating'           => 5,
                'comment'          => '何食べても美味しいよ。よだれシリーズがいい。1人あたりの料金 ￥3,000～4,000程度。',
                'reply_comment'    => null,
                'replied_at'       => null,
                'reviewed_at'      => now()->subMonths(3),
            ],
            [
                'google_review_id' => 'tonkyu_review_004',
                'reviewer_name'    => 'Yutaka Ikeda',
                'rating'           => 5,
                'comment'          => '1人あたりの料金 ￥1,000～2,000。食事: 5、サービス: 5、雰囲気: 5。',
                'reply_comment'    => null,
                'replied_at'       => null,
                'reviewed_at'      => now()->subMonths(11),
            ],
        ];

        foreach ($reviews as $data) {
            GoogleReview::updateOrCreate(
                ['google_review_id' => $data['google_review_id']],
                array_merge($data, ['store_id' => $store->id])
            );
        }

        $this->command->info("とん球: store_id={$store->id}, " . count($reviews) . '件の口コミを登録しました。');
    }
}
