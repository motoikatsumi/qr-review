<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessType;
use App\Models\Store;
use App\Models\StorePostTemplate;

class ShichiAssistSeeder extends Seeder
{
    /**
     * 質屋アシスト用の初期データを投入
     * 5店舗 + 各店舗のAI設定 + 投稿フッターテンプレート
     */
    public function run()
    {
        $pawnType = BusinessType::where('slug', 'pawn')->first();
        $pawnTypeId = $pawnType?->id;

        // 共通設定
        $commonSettings = [
            'business_type_id'     => $pawnTypeId,
            'notify_email'         => 'shichi@assist-grp.jp',
            'notify_threshold'     => 3,
            'is_active'            => true,
            'ai_store_description' => '質屋・買取店 質屋アシスト のスタッフ',
            'ai_tone_preference'   => 'auto',
            'ai_custom_instruction' => '',
            'ai_reply_instruction' => '',
            'ai_extra_ng_words'    => implode("\n", [
                '質屋アシスト',
                'アシストグループ',
                '他店名',
                '買取価格の具体的な金額',
                '鑑定書の内容',
                '質流れ',
                '偽物',
                'コピー品',
            ]),
            'ai_reply_length'      => 'medium',
            'ai_suggestion_length' => 'medium',
            'ai_service_keywords'  => implode("\n", [
                '高価買取',
                '買取・査定',
                '査定・鑑定',
                '質預かり・買取',
                '無料査定',
            ]),
            'meo_keywords' => '買取,質屋,査定,鑑定,宝石,ブランド',
            'meo_ratio'    => 30,
        ];

        // 店舗別設定（nameKeyword で既存店舗を検索して更新する）
        $stores = [
            [
                'nameKeyword' => '西千石',
                'ai_area_keywords' => implode("\n", [
                    '「鹿児島市」「西千石町」という地名を自然に1回ずつ混ぜる',
                    '「鹿児島市」「天文館」という地名を自然に1回ずつ混ぜる',
                    '「鹿児島」「西千石」という地名を自然に1回ずつ混ぜる',
                    '「鹿児島市」という地名と「天文館エリア」という表現を自然に1回ずつ混ぜる',
                    '「鹿児島市西千石」「中央駅周辺」という地名を自然に1回ずつ混ぜる',
                ]),
                'footer_template' => "鹿児島市西千石町・天文館・中央駅周辺エリアで○○の売却や質預かりをご検討の方は、高価買取の質屋アシスト西千石店へぜひご相談ください。LINE査定も受付中です。",
            ],
            [
                'nameKeyword' => '宇宿',
                'ai_area_keywords' => implode("\n", [
                    '「鹿児島市」「宇宿」という地名を自然に1回ずつ混ぜる',
                    '「鹿児島市宇宿」「谷山エリア」という地名を自然に1回ずつ混ぜる',
                    '「鹿児島」「宇宿・谷山」という地名を自然に1回ずつ混ぜる',
                    '「鹿児島市」という地名と「宇宿エリア」という表現を自然に1回ずつ混ぜる',
                ]),
                'footer_template' => "鹿児島市宇宿・谷山・紫原エリアで○○の売却や質預かりをご検討の方は、高価買取の質屋アシスト宇宿店へぜひご相談ください。LINE査定も受付中です。",
            ],
            [
                'nameKeyword' => '伊敷',
                'ai_area_keywords' => implode("\n", [
                    '「鹿児島市」「伊敷」という地名を自然に1回ずつ混ぜる',
                    '「鹿児島市伊敷」「草牟田」という地名を自然に1回ずつ混ぜる',
                    '「鹿児島」「伊敷・下伊敷」という地名を自然に1回ずつ混ぜる',
                    '「鹿児島市」という地名と「伊敷・吉野エリア」という表現を自然に1回ずつ混ぜる',
                ]),
                'footer_template' => "鹿児島市伊敷・草牟田・下伊敷・吉野エリアで○○の売却や質預かりをご検討の方は、高価買取の質屋アシスト伊敷店へぜひご相談ください。LINE査定も受付中です。",
            ],
            [
                'nameKeyword' => '鹿屋',
                'ai_area_keywords' => implode("\n", [
                    '「鹿屋市」「寿」という地名を自然に1回ずつ混ぜる',
                    '「鹿屋市」「札元」という地名を自然に1回ずつ混ぜる',
                    '「鹿屋」「寿・札元・川西エリア」という地名を自然に1回ずつ混ぜる',
                    '「鹿屋市」という地名と「川西エリア」という表現を自然に1回ずつ混ぜる',
                ]),
                'footer_template' => "鹿屋市寿・川西・札元エリアで○○の売却や質預かりをご検討の方は、高価買取の質屋アシスト鹿屋店へぜひご相談ください。LINE査定も受付中です。",
            ],
            [
                'nameKeyword' => '国分',
                'ai_area_keywords' => implode("\n", [
                    '「霧島市」「国分」という地名を自然に1回ずつ混ぜる',
                    '「霧島市国分」「隼人」という地名を自然に1回ずつ混ぜる',
                    '「霧島」「国分・隼人エリア」という地名を自然に1回ずつ混ぜる',
                    '「霧島市」という地名と「国分エリア」という表現を自然に1回ずつ混ぜる',
                ]),
                'footer_template' => "霧島市国分・隼人エリアで○○の売却や質預かりをご検討の方は、高価買取の質屋アシスト国分店へぜひご相談ください。LINE査定も受付中です。",
            ],
        ];

        foreach ($stores as $storeData) {
            $keyword = $storeData['nameKeyword'];
            $footerTemplate = $storeData['footer_template'];
            unset($storeData['nameKeyword'], $storeData['footer_template']);

            // 既存店舗を名前で検索
            $store = Store::where('name', 'like', "%{$keyword}%")->first();

            if ($store) {
                // 既存店舗にAI設定を追加更新（name, slug, google_review_url は既存値を維持）
                $store->update(array_merge($commonSettings, $storeData));
            } else {
                // 店舗が見つからない場合は新規作成
                $store = Store::create(array_merge($commonSettings, $storeData, [
                    'name' => '質屋アシスト ' . $keyword . '店',
                    'slug' => \Illuminate\Support\Str::random(8),
                    'google_review_url' => '',
                ]));
            }

            StorePostTemplate::updateOrCreate(
                ['store_id' => $store->id],
                ['template_text' => $footerTemplate]
            );
        }
    }
}
