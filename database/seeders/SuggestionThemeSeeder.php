<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SuggestionCategory;
use App\Models\SuggestionTheme;

class SuggestionThemeSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => '価格・金額',
                'sort_order' => 1,
                'themes' => [
                    ['icon' => '💰', 'label' => '高価買取', 'keyword' => '査定価格が高くて満足、高価買取', 'sort_order' => 1],
                    ['icon' => '💴', 'label' => '金額に満足', 'keyword' => '買取金額に満足した、思った以上の金額で嬉しかった、納得の価格', 'sort_order' => 2],
                    ['icon' => '📈', 'label' => '価格が安定', 'keyword' => '価格が安定していて安心できた、相場に合った価格で良心的だった', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'サービス・対応',
                'sort_order' => 2,
                'themes' => [
                    ['icon' => '📋', 'label' => '丁寧な査定', 'keyword' => '査定が丁寧でわかりやすく親切に説明してくれた', 'sort_order' => 1],
                    ['icon' => '😊', 'label' => 'スタッフが親切', 'keyword' => 'スタッフの対応が親切で感じが良かった', 'sort_order' => 2],
                    ['icon' => '⚡', 'label' => '素早い対応', 'keyword' => '査定がスムーズで待ち時間が短く素早く対応してもらえた', 'sort_order' => 3],
                ],
            ],
            [
                'name' => '店舗の雰囲気',
                'sort_order' => 3,
                'themes' => [
                    ['icon' => '✨', 'label' => '店内がきれい', 'keyword' => '店内がきれいで清潔感があり、居心地が良かった', 'sort_order' => 1],
                    ['icon' => '🏠', 'label' => '雰囲気が良い', 'keyword' => 'お店の雰囲気が良く、落ち着いて利用できた', 'sort_order' => 2],
                    ['icon' => '📍', 'label' => 'アクセスが便利', 'keyword' => '立地が良くアクセスしやすい、駐車場も完備', 'sort_order' => 3],
                ],
            ],
            [
                'name' => '安心感・信頼',
                'sort_order' => 4,
                'themes' => [
                    ['icon' => '🔰', 'label' => '初めてでも安心', 'keyword' => '初めての利用でも緊張せず安心して利用できた', 'sort_order' => 1],
                    ['icon' => '💬', 'label' => '相談しやすい', 'keyword' => '気軽に相談できる雰囲気で、押し売り感がなく安心だった、信頼できる', 'sort_order' => 2],
                    ['icon' => '🤝', 'label' => 'また利用したい', 'keyword' => 'また利用したい、リピートしたい、おすすめできる', 'sort_order' => 3],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $themes = $catData['themes'];
            unset($catData['themes']);

            $category = SuggestionCategory::firstOrCreate(
                ['name' => $catData['name']],
                $catData
            );

            foreach ($themes as $theme) {
                $category->themes()->firstOrCreate(
                    ['label' => $theme['label']],
                    $theme
                );
            }
        }
    }
}
