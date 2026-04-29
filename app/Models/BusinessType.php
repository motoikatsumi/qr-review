<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'base_context',
        'focus_presets',
        'style_presets',
        'ng_words',
        'visit_type_options',
        'review_item_options',
        'review_option_groups',
        'use_pawn_system',
        'use_purchase_posts',
        'post_categories',
        'post_title_template',
        'post_action_word',
        'use_product_rank',
        'post_status_options',
        'post_reason_presets',
        'post_accessory_presets',
        'post_hidden_fields',
        'post_default_hashtags',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'focus_presets'          => 'array',
        'style_presets'          => 'array',
        'ng_words'               => 'array',
        'visit_type_options'     => 'array',
        'review_item_options'    => 'array',
        'review_option_groups'   => 'array',
        'post_categories'        => 'array',
        'post_status_options'    => 'array',
        'post_reason_presets'    => 'array',
        'post_accessory_presets' => 'array',
        'post_hidden_fields'     => 'array',
        'use_pawn_system'        => 'boolean',
        'use_purchase_posts'     => 'boolean',
        'use_product_rank'       => 'boolean',
        'is_active'              => 'boolean',
    ];

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function suggestionCategories()
    {
        return $this->hasMany(SuggestionCategory::class);
    }

    public function replyCategories()
    {
        return $this->hasMany(ReplyCategory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * レビューフォーム描画用：有効な質問グループ（enabled=true）を順序通りに返す。
     * review_option_groups が未設定なら、visit_type_options / review_item_options と
     * 従来のハードコード（性別・年代）から互換的に組み立てる。
     *
     * @return array<int, array{key:string,label:string,options:array}>
     */
    public function activeReviewOptionGroups(): array
    {
        $groups = $this->review_option_groups;
        if (is_array($groups) && count($groups) > 0) {
            return array_values(array_filter($groups, fn($g) => !empty($g['enabled']) && !empty($g['options'])));
        }

        // 後方互換フォールバック（review_option_groups 未設定時）
        $fallback = [];
        $fallback[] = [
            'key'     => 'gender',
            'label'   => '性別',
            'options' => ['男性', '女性'],
            'enabled' => true,
        ];
        $fallback[] = [
            'key'     => 'visit_type',
            'label'   => '来店',
            'options' => !empty($this->visit_type_options) ? $this->visit_type_options : ['新規', 'リピーター'],
            'enabled' => true,
        ];
        $fallback[] = [
            'key'     => 'age',
            'label'   => '年代',
            'options' => ['20', '30', '40', '50', '60'],
            'enabled' => true,
        ];
        if (!empty($this->review_item_options)) {
            $fallback[] = [
                'key'     => 'item',
                'label'   => '品目',
                'options' => $this->review_item_options,
                'enabled' => true,
            ];
        }
        return $fallback;
    }
}
