<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    protected $fillable = [
        'action',
        'store_id',
        'user_id',
        'tokens_used',
    ];

    /**
     * 今月の利用回数を取得
     */
    public static function monthlyCount(): int
    {
        return static::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
    }
}
