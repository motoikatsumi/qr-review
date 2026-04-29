<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $connection = 'master';

    protected $fillable = [
        'company_name',
        'subdomain',
        'db_name',
        'db_username',
        'db_password',
        'plan',
        'monthly_fee_per_store',
        'monthly_fee_override',
        'billing_company_name',
        'billing_postal_code',
        'billing_address',
        'contact_email',
        'contact_name',
        'ai_monthly_limit',
        'is_active',
        'contract_start',
        'contract_end',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'contract_start' => 'date',
        'contract_end' => 'date',
        'monthly_fee_per_store' => 'integer',
        'monthly_fee_override' => 'integer',
    ];

    /**
     * プラン別のAI月間上限
     */
    public static function planLimits(): array
    {
        return [
            'light'    => 50,
            'standard' => 200,
            'pro'      => 9999,
        ];
    }

    /**
     * 月額料金を計算（テナント DB から店舗数取得）
     * - monthly_fee_override が設定されていればそれを使用
     * - そうでなければ monthly_fee_per_store × 店舗数
     */
    public function calculateMonthlyFee(?int $storeCount = null): int
    {
        if (!is_null($this->monthly_fee_override) && $this->monthly_fee_override > 0) {
            return (int) $this->monthly_fee_override;
        }
        $perStore = (int) ($this->monthly_fee_per_store ?? 11000);
        $count = $storeCount ?? 0;
        return $perStore * $count;
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * 現在のリクエストコンテキストに紐付くテナントを取得
     * - 本番（サブドメイン）: TenantDatabase ミドルウェアで設定された current_tenant を使用
     * - ローカル開発: 現在の DB 接続先名と一致するテナントを検索
     */
    public static function current(): ?self
    {
        // 1. ミドルウェアで設定済みなら最優先
        if (app()->bound('current_tenant')) {
            $t = app('current_tenant');
            if ($t instanceof self) return $t;
            if (is_object($t) && isset($t->id)) {
                return self::find($t->id);
            }
        }

        // 2. ローカル開発: DB 名でマッチング
        try {
            $currentDb = \Illuminate\Support\Facades\DB::connection('mysql')->getDatabaseName();
            if ($currentDb) {
                return self::where('db_name', $currentDb)->first();
            }
        } catch (\Throwable $e) {
            // 無視
        }
        return null;
    }
}
