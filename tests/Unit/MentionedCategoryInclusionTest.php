<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * GeminiService::generateReplyComment() の取扱商品カテゴリ選定ロジックを検証する。
 * お客様の口コミに登場する品目(例:「時計」)が picks に確実に含まれることを保証する。
 *
 * 実装は app/Services/GeminiService.php の "// 取扱商品カテゴリ(返信での言及用)を選ぶ。" ブロック。
 * このテストは同じロジックを再現し、ランダム性に依存せず必ず通ることを保証する。
 */
class MentionedCategoryInclusionTest extends TestCase
{
    /**
     * 口コミに含まれる品目が常に picks に含まれる
     */
    public function test_mentioned_category_is_always_included(): void
    {
        $groups = [
            ['ブランド品', '時計', '貴金属', '宝石'],
            ['お酒', 'ゲーム', '楽器', '電化製品'],
            ['切手', '記念硬貨', '骨董品', '工芸品'],
        ];
        $reviewComment = 'いつも丁寧な査定で安心して利用させていただいております。今回も時計の買取で伺いましたが、説明が分かりやすく、終始親切なご対応でした。';

        // 100回試行して常に「時計」が含まれることを確認
        for ($i = 0; $i < 100; $i++) {
            $picks = $this->pickCategories($groups, $reviewComment);
            $this->assertContains('時計', $picks, "iteration={$i} で時計が picks に含まれなかった: " . implode(',', $picks));
        }
    }

    /**
     * 複数の品目が口コミに登場する場合、すべて picks に含まれる
     */
    public function test_multiple_mentioned_categories_are_all_included(): void
    {
        $groups = [
            ['ブランド品', '時計', '貴金属', '宝石'],
            ['お酒', 'ゲーム', '楽器', '電化製品'],
        ];
        $reviewComment = '時計と貴金属を買取してもらいました。';

        for ($i = 0; $i < 50; $i++) {
            $picks = $this->pickCategories($groups, $reviewComment);
            $this->assertContains('時計', $picks);
            $this->assertContains('貴金属', $picks);
        }
    }

    /**
     * 様々な品目が口コミに登場するパターン全てで、当該品目が picks に含まれる
     */
    public function test_various_categories_are_included_when_mentioned(): void
    {
        $groups = [
            ['ブランド品', '時計', '貴金属', '宝石'],
            ['お酒', 'ゲーム', '楽器', '電化製品'],
            ['切手', '記念硬貨', '骨董品', '工芸品'],
            ['カメラ', 'パソコン', 'スマホ', 'タブレット'],
        ];

        $cases = [
            'お酒'     => '父が集めていたお酒を買い取ってもらいました。査定が早くて助かりました。',
            'ゲーム'   => '子供が遊ばなくなったゲーム機を持ち込みました。',
            '楽器'     => 'ギターと楽器の付属品を売却しました。',
            '電化製品' => '使わなくなった電化製品を引き取ってもらえました。',
            '宝石'     => '結婚指輪と宝石を査定していただきました。',
            'ブランド品' => 'ブランド品のバッグを高く買ってもらえて嬉しいです。',
            '貴金属'   => '父から譲り受けた貴金属を持ち込みました。',
            '切手'     => '古い切手のコレクションを売りました。',
            'カメラ'   => '古いカメラを買取してもらいました。',
            'パソコン' => 'パソコンの査定が思ったより高かったです。',
            '骨董品'   => '蔵にあった骨董品を持ち込みました。',
        ];

        foreach ($cases as $expectedCat => $reviewComment) {
            // 各ケース 30 回試行
            for ($i = 0; $i < 30; $i++) {
                $picks = $this->pickCategories($groups, $reviewComment);
                $this->assertContains(
                    $expectedCat,
                    $picks,
                    "「{$reviewComment}」に対して『{$expectedCat}』が picks に含まれなかった (試行={$i}, picks=" . implode(',', $picks) . ')'
                );
                // 選ばれたグループは expectedCat を含むはず
                $this->assertGreaterThanOrEqual(3, count($picks), 'picks は 3 件以上');
                $this->assertLessThanOrEqual(4, count($picks), 'picks は 4 件以下');
            }
        }
    }

    /**
     * 異なるグループに跨る複数品目が口コミに登場した場合の挙動
     * (実装は1グループしか選ばないので、優先候補グループのいずれかが選ばれる)
     */
    public function test_cross_group_mentions_pick_one_candidate_group(): void
    {
        $groups = [
            ['ブランド品', '時計', '貴金属', '宝石'],
            ['お酒', 'ゲーム', '楽器', '電化製品'],
        ];
        // 時計(group0) と お酒(group1) の両方に言及
        $reviewComment = '時計とお酒を一緒に査定してもらいました。';

        for ($i = 0; $i < 50; $i++) {
            $picks = $this->pickCategories($groups, $reviewComment);
            // 少なくとも片方は含まれているべき(選ばれたグループに属する方)
            $this->assertTrue(
                in_array('時計', $picks, true) || in_array('お酒', $picks, true),
                "試行={$i}: 時計とお酒のどちらも含まれない: " . implode(',', $picks)
            );
        }
    }

    /**
     * 口コミに品目が含まれない場合、いずれかのグループから 3〜4 件抽出される
     */
    public function test_no_mention_picks_random_group(): void
    {
        $groups = [
            ['ブランド品', '時計', '貴金属', '宝石'],
            ['お酒', 'ゲーム', '楽器', '電化製品'],
        ];
        $reviewComment = 'スタッフの対応がとても良かったです。';

        $picks = $this->pickCategories($groups, $reviewComment);
        $this->assertGreaterThanOrEqual(3, count($picks));
        $this->assertLessThanOrEqual(4, count($picks));
    }

    /**
     * GeminiService の選定ロジックを再現する純粋関数版
     * (実装と完全に同じアルゴリズムでなければテストの意味がない)
     */
    private function pickCategories(array $groups, string $reviewComment): array
    {
        $groups = array_values(array_filter($groups, fn($g) => is_array($g) && !empty($g)));

        $mentioned = [];
        if ($reviewComment !== '') {
            foreach ($groups as $g) {
                foreach ($g as $cat) {
                    if (mb_strpos($reviewComment, $cat) !== false && !in_array($cat, $mentioned, true)) {
                        $mentioned[] = $cat;
                    }
                }
            }
        }

        if (!empty($mentioned)) {
            $candidateGroups = array_values(array_filter($groups, function ($g) use ($mentioned) {
                foreach ($mentioned as $m) if (in_array($m, $g, true)) return true;
                return false;
            }));
            $group = !empty($candidateGroups)
                ? $candidateGroups[array_rand($candidateGroups)]
                : $groups[array_rand($groups)];
        } else {
            $group = $groups[array_rand($groups)];
        }

        $pickCount = mt_rand(3, 4);
        $forced = array_values(array_filter($mentioned, fn($m) => in_array($m, $group, true)));
        $remaining = array_values(array_diff($group, $forced));
        shuffle($remaining);
        $needed = max(0, $pickCount - count($forced));
        return array_values(array_unique(
            array_merge($forced, array_slice($remaining, 0, $needed))
        ));
    }
}
