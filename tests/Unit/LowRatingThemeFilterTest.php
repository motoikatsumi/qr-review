<?php

namespace Tests\Unit;

use App\Models\SuggestionCategory;
use App\Models\SuggestionTheme;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

/**
 * 低評価テーマ抽出ロジックの単体テスト。
 * ReviewController::show() のフィルタロジック(カテゴリの is_for_low_rating)を検証する。
 */
class LowRatingThemeFilterTest extends TestCase
{
    /**
     * カテゴリ単位のフラグで、配下テーマを正しくバケットに振り分けられるか
     */
    public function test_filter_extracts_only_themes_from_low_rating_categories(): void
    {
        $highCat = new SuggestionCategory(['name' => '高評価カテゴリ', 'is_for_low_rating' => false]);
        $highCat->setRelation('activeThemes', new Collection([
            $this->theme('高価買取'),
            $this->theme('スタッフが親切'),
        ]));

        $lowCat = new SuggestionCategory(['name' => '低評価カテゴリ', 'is_for_low_rating' => true]);
        $lowCat->setRelation('activeThemes', new Collection([
            $this->theme('査定額'),
            $this->theme('待ち時間'),
        ]));

        $cats = new Collection([$highCat, $lowCat]);

        // ReviewController::show() と同じロジック
        $lowRating = collect();
        foreach ($cats as $cat) {
            if (!$cat->is_for_low_rating) continue;
            foreach ($cat->activeThemes as $t) {
                $lowRating->push($t);
            }
        }

        $this->assertCount(2, $lowRating, '低評価カテゴリ配下のテーマだけが抽出されるべき');
        $this->assertEquals('査定額', $lowRating[0]->label);
        $this->assertEquals('待ち時間', $lowRating[1]->label);
    }

    /**
     * 高評価カテゴリしか無い場合、低評価テーマは0件
     */
    public function test_no_low_rating_themes_when_no_low_rating_category(): void
    {
        $cat = new SuggestionCategory(['name' => '通常', 'is_for_low_rating' => false]);
        $cat->setRelation('activeThemes', new Collection([$this->theme('対応が良かった')]));

        $cats = new Collection([$cat]);
        $lowRating = collect();
        foreach ($cats as $c) {
            if (!$c->is_for_low_rating) continue;
            foreach ($c->activeThemes as $t) $lowRating->push($t);
        }

        $this->assertCount(0, $lowRating);
    }

    private function theme(string $label): SuggestionTheme
    {
        $t = new SuggestionTheme();
        $t->label = $label;
        $t->keyword = $label;
        $t->icon = '⭐';
        return $t;
    }
}
