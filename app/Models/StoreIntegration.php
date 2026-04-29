<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class StoreIntegration extends Model
{
    protected $fillable = [
        'store_id',
        'service',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'extra_data',
        'is_active',
    ];

    protected $casts = [
        'extra_data'       => 'array',
        'token_expires_at' => 'datetime',
        'is_active'        => 'boolean',
    ];

    // =============================================
    // アクセストークンを自動暗号化/復号化
    // =============================================

    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute($value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setRefreshTokenAttribute($value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute($value): ?string
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    // =============================================
    // リレーション
    // =============================================

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // =============================================
    // ヘルパー
    // =============================================

    /** トークンが期限切れかどうか */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) return false;
        return $this->token_expires_at->isPast();
    }

    /** サービス表示名 */
    public function getServiceLabelAttribute(): string
    {
        return match($this->service) {
            'instagram' => 'Instagram',
            'facebook'  => 'Facebook',
            'wordpress' => 'WordPress',
            default     => $this->service,
        };
    }
}
