<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultHashtagsSeeder extends Seeder
{
    /**
     * 業種・店舗のデフォルトハッシュタグを設定
     */
    public function run()
    {
        // 質屋・買取店（業種ID=1）のデフォルトハッシュタグ
        DB::table('business_types')->where('id', 1)->update([
            'post_default_hashtags' => implode("\n", [
                '買取', '高価買取', '査定', '無料査定', '即日現金化',
                '質屋', 'ブランド買取', '鹿児島', '質屋アシスト',
            ]),
        ]);

        // 各店舗のハッシュタグ
        $storeHashtags = [
            1 => ['質屋アシスト西千石店', '鹿児島市', '西千石町', '天文館'],
            2 => ['質屋アシスト宇宿店', '鹿児島市', '宇宿', '谷山'],
            3 => ['質屋アシスト伊敷店', '鹿児島市', '伊敷', '草牟田'],
            4 => ['質屋アシスト鹿屋店', '鹿屋市', '鹿屋'],
            5 => ['質屋アシスト国分店', '霧島市', '国分', '隼人'],
        ];

        foreach ($storeHashtags as $id => $tags) {
            DB::table('stores')->where('id', $id)->update([
                'custom_hashtags' => implode("\n", $tags),
            ]);
        }
    }
}
