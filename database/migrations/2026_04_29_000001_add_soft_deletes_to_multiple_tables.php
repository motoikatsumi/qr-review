<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 論理削除（ゴミ箱）対応のため複数テーブルに deleted_at を追加。
     * 対象: stores / users / business_types / suggestion_categories / suggestion_themes
     *      / reply_categories / reply_keywords / purchase_posts
     * Review は既に SoftDeletes 実装済みのため対象外。
     */
    private array $tables = [
        'stores',
        'users',
        'business_types',
        'suggestion_categories',
        'suggestion_themes',
        'reply_categories',
        'reply_keywords',
        'purchase_posts',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropSoftDeletes();
                });
            }
        }
    }
};
