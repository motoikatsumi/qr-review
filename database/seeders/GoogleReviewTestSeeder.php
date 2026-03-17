<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GoogleReview;
use App\Models\ReplyCategory;
use App\Models\ReplyKeyword;

class GoogleReviewTestSeeder extends Seeder
{
    public function run()
    {
        // === 返信カテゴリ・キーワード（MEO対策：取扱商品カテゴリ） ===
        $categories = [
            1  => '貴金属',
            2  => 'ブランド品',
            3  => '時計',
            4  => '電動工具',
            5  => 'スマホ',
            6  => 'タブレット',
            7  => 'パソコン',
            8  => '電化製品',
            9  => '金券類（非課税）',
            10 => '楽器',
            11 => 'ゲーム・玩具',
            12 => 'お酒',
            13 => 'カメラ・レンズ',
            14 => 'スポーツ用品',
            15 => 'カー用品',
            16 => '釣り用品',
            17 => '喫煙用品',
            18 => '美容器具・健康器具・化粧品(未開封)',
            19 => '衣類・靴',
            20 => 'その他',
            21 => '音響機器',
            22 => 'アウトドア用品',
            23 => '家具',
            24 => '住宅設備品',
            25 => 'CD・DVD',
            26 => '陶芸品',
            27 => '本・コミック',
            28 => '金券類（課税：切手・レターパック・印紙・金額のない株主優待券）',
        ];

        $cats = [];
        foreach ($categories as $order => $name) {
            $cats[$order] = ReplyCategory::create(['name' => $name, 'sort_order' => $order]);
        }

        // カテゴリごとのキーワード定義
        $keywords = [
            // 貴金属
            1 => [
                ['label' => '金', 'keyword' => '金'],
                ['label' => 'プラチナ', 'keyword' => 'プラチナ'],
                ['label' => '宝石', 'keyword' => '宝石 ダイヤモンド'],
                ['label' => '貴金属買取', 'keyword' => '貴金属 買取 査定'],
            ],
            // ブランド品
            2 => [
                ['label' => 'ブランドバッグ', 'keyword' => 'ブランドバッグ'],
                ['label' => 'ブランド財布', 'keyword' => 'ブランド財布'],
                ['label' => 'ブランド品買取', 'keyword' => 'ブランド品 買取 査定'],
            ],
            // 時計
            3 => [
                ['label' => '腕時計', 'keyword' => '腕時計'],
                ['label' => '高級時計', 'keyword' => '高級時計 ブランド時計'],
                ['label' => '時計買取', 'keyword' => '時計 買取 査定'],
            ],
            // 電動工具
            4 => [
                ['label' => '電動工具', 'keyword' => '電動工具'],
                ['label' => '工具買取', 'keyword' => '工具 買取 査定'],
                ['label' => 'マキタ・ハイコーキ', 'keyword' => 'マキタ ハイコーキ'],
            ],
            // スマホ
            5 => [
                ['label' => 'iPhone', 'keyword' => 'iPhone スマホ'],
                ['label' => 'スマートフォン', 'keyword' => 'スマートフォン 買取'],
                ['label' => 'Android', 'keyword' => 'Android Galaxy Xperia'],
            ],
            // タブレット
            6 => [
                ['label' => 'iPad', 'keyword' => 'iPad タブレット'],
                ['label' => 'タブレット買取', 'keyword' => 'タブレット 買取 査定'],
            ],
            // パソコン
            7 => [
                ['label' => 'ノートパソコン', 'keyword' => 'ノートパソコン PC'],
                ['label' => 'Mac', 'keyword' => 'Mac MacBook'],
                ['label' => 'パソコン買取', 'keyword' => 'パソコン 買取 査定'],
            ],
            // 電化製品
            8 => [
                ['label' => '家電', 'keyword' => '家電 電化製品'],
                ['label' => '生活家電', 'keyword' => '生活家電 買取'],
                ['label' => '美容家電', 'keyword' => '美容家電 ドライヤー'],
            ],
            // 金券類（非課税）
            9 => [
                ['label' => '商品券', 'keyword' => '商品券 ギフト券'],
                ['label' => '金券買取', 'keyword' => '金券 買取'],
                ['label' => '旅行券', 'keyword' => '旅行券 ビール券'],
            ],
            // 楽器
            10 => [
                ['label' => 'ギター', 'keyword' => 'ギター エレキギター'],
                ['label' => '楽器買取', 'keyword' => '楽器 買取 査定'],
                ['label' => '管楽器・鍵盤', 'keyword' => '管楽器 キーボード 電子ピアノ'],
            ],
            // ゲーム・玩具
            11 => [
                ['label' => 'ゲーム機', 'keyword' => 'ゲーム機 Switch PS5'],
                ['label' => 'フィギュア', 'keyword' => 'フィギュア プラモデル'],
                ['label' => 'ゲームソフト', 'keyword' => 'ゲームソフト 買取'],
            ],
            // お酒
            12 => [
                ['label' => 'ウイスキー', 'keyword' => 'ウイスキー ブランデー'],
                ['label' => 'ワイン', 'keyword' => 'ワイン シャンパン'],
                ['label' => 'お酒買取', 'keyword' => 'お酒 買取 査定'],
            ],
            // カメラ・レンズ
            13 => [
                ['label' => 'デジタルカメラ', 'keyword' => 'デジタルカメラ 一眼レフ'],
                ['label' => 'レンズ', 'keyword' => 'レンズ 交換レンズ'],
                ['label' => 'カメラ買取', 'keyword' => 'カメラ 買取 査定'],
            ],
            // スポーツ用品
            14 => [
                ['label' => 'ゴルフ', 'keyword' => 'ゴルフ ゴルフクラブ'],
                ['label' => 'スポーツ用品', 'keyword' => 'スポーツ用品 買取'],
                ['label' => 'スキー・スノボ', 'keyword' => 'スキー スノーボード'],
            ],
            // カー用品
            15 => [
                ['label' => 'タイヤ・ホイール', 'keyword' => 'タイヤ ホイール'],
                ['label' => 'カーナビ', 'keyword' => 'カーナビ ドラレコ'],
                ['label' => 'カー用品買取', 'keyword' => 'カー用品 買取'],
            ],
            // 釣り用品
            16 => [
                ['label' => 'リール', 'keyword' => 'リール 釣竿'],
                ['label' => '釣り具買取', 'keyword' => '釣り具 釣り用品 買取'],
            ],
            // 喫煙用品
            17 => [
                ['label' => 'ライター', 'keyword' => 'ライター ジッポ'],
                ['label' => '喫煙具買取', 'keyword' => '喫煙具 喫煙用品 買取'],
            ],
            // 美容器具・健康器具・化粧品(未開封)
            18 => [
                ['label' => '美容器具', 'keyword' => '美容器具 美顔器'],
                ['label' => '健康器具', 'keyword' => '健康器具 マッサージ器'],
                ['label' => '化粧品', 'keyword' => '化粧品 未開封 コスメ'],
            ],
            // 衣類・靴
            19 => [
                ['label' => 'ブランド衣類', 'keyword' => 'ブランド衣類 洋服'],
                ['label' => 'ブランド靴', 'keyword' => 'ブランド靴 スニーカー'],
                ['label' => '衣類買取', 'keyword' => '衣類 靴 買取'],
            ],
            // その他
            20 => [
                ['label' => '不用品', 'keyword' => '不用品 買取'],
                ['label' => '出張買取', 'keyword' => '出張買取 査定'],
            ],
            // 音響機器
            21 => [
                ['label' => 'スピーカー', 'keyword' => 'スピーカー アンプ'],
                ['label' => 'オーディオ', 'keyword' => 'オーディオ ヘッドホン イヤホン'],
                ['label' => '音響機器買取', 'keyword' => '音響機器 買取'],
            ],
            // アウトドア用品
            22 => [
                ['label' => 'テント', 'keyword' => 'テント キャンプ用品'],
                ['label' => 'アウトドア買取', 'keyword' => 'アウトドア用品 買取'],
                ['label' => '登山用品', 'keyword' => '登山用品 トレッキング'],
            ],
            // 家具
            23 => [
                ['label' => 'ブランド家具', 'keyword' => 'ブランド家具 デザイナーズ'],
                ['label' => '家具買取', 'keyword' => '家具 買取 査定'],
            ],
            // 住宅設備品
            24 => [
                ['label' => '住宅設備', 'keyword' => '住宅設備 買取'],
                ['label' => '建材', 'keyword' => '建材 水栓 照明器具'],
            ],
            // CD・DVD
            25 => [
                ['label' => 'CD', 'keyword' => 'CD DVD Blu-ray'],
                ['label' => 'レコード', 'keyword' => 'レコード 買取'],
            ],
            // 陶芸品
            26 => [
                ['label' => '陶芸品', 'keyword' => '陶芸品 焼き物 陶器'],
                ['label' => '骨董品', 'keyword' => '骨董品 美術品 買取'],
            ],
            // 本・コミック
            27 => [
                ['label' => '本', 'keyword' => '本 書籍 古本'],
                ['label' => 'コミック', 'keyword' => 'コミック 漫画 買取'],
            ],
            // 金券類（課税）
            28 => [
                ['label' => '切手', 'keyword' => '切手 レターパック'],
                ['label' => '印紙', 'keyword' => '印紙 収入印紙'],
                ['label' => '株主優待券', 'keyword' => '株主優待券 買取'],
            ],
        ];

        foreach ($keywords as $catOrder => $kwList) {
            foreach ($kwList as $i => $kw) {
                ReplyKeyword::create([
                    'category_id' => $cats[$catOrder]->id,
                    'label'       => $kw['label'],
                    'keyword'     => $kw['keyword'],
                    'sort_order'  => $i + 1,
                ]);
            }
        }

        // === テスト用Google口コミ ===
        $storeId = \App\Models\Store::first()->id;

        GoogleReview::create([
            'store_id' => $storeId,
            'google_review_id' => 'test_review_001',
            'reviewer_name' => '田中太郎',
            'rating' => 5,
            'comment' => '初めて利用しましたが、スタッフの方がとても親切で安心できました。査定も丁寧に説明していただき、納得のいく価格で買い取っていただけました。また利用したいです。',
            'reviewed_at' => now()->subDays(3),
        ]);

        GoogleReview::create([
            'store_id' => $storeId,
            'google_review_id' => 'test_review_002',
            'reviewer_name' => '山田花子',
            'rating' => 4,
            'comment' => '店内がきれいで落ち着いた雰囲気でした。買取価格も思ったより良かったです。',
            'reviewed_at' => now()->subDays(5),
        ]);

        GoogleReview::create([
            'store_id' => $storeId,
            'google_review_id' => 'test_review_003',
            'reviewer_name' => '佐藤一郎',
            'rating' => 2,
            'comment' => '待ち時間が長かったのが少し残念でした。査定額はまあまあだったと思います。',
            'reviewed_at' => now()->subDays(7),
        ]);

        GoogleReview::create([
            'store_id' => $storeId,
            'google_review_id' => 'test_review_004',
            'reviewer_name' => '鈴木美咲',
            'rating' => 5,
            'comment' => null,
            'reviewed_at' => now()->subDays(10),
        ]);

        GoogleReview::create([
            'store_id' => $storeId,
            'google_review_id' => 'test_review_005',
            'reviewer_name' => '高橋健太',
            'rating' => 3,
            'comment' => '普通の質屋さんという感じ。可もなく不可もなく。',
            'reviewed_at' => now()->subDay(),
        ]);

        $this->command->info('テストデータ投入完了: カテゴリ' . ReplyCategory::count() . '件、キーワード' . ReplyKeyword::count() . '件、口コミ5件');
    }
}
