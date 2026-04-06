<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PawnSystemService
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.pawn_system.url', ''), '/');
        $this->token = config('services.pawn_system.token', '');
    }

    /**
     * 管理番号で在庫情報を取得
     */
    public function getStockByManageNumber(string $manageNumber): ?array
    {
        if (empty($this->baseUrl) || empty($this->token)) {
            Log::warning('PawnSystemService: API URLまたはトークンが未設定');
            return null;
        }

        try {
            $response = Http::withToken($this->token)
                ->accept('application/json')
                ->timeout(10)
                ->get("{$this->baseUrl}/api/stock/{$manageNumber}");

            if ($response->successful()) {
                $data = $response->json();
                if ($data['success'] ?? false) {
                    return $this->formatForPurchasePost($data['data']);
                }
            }

            if ($response->status() === 404) {
                return null;
            }

            Log::warning('PawnSystemService: APIエラー', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PawnSystemService: 通信エラー', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * pawn-systemのレスポンスをqr-reviewのフォーム項目にマッピング
     */
    private function formatForPurchasePost(array $data): array
    {
        // ブランド名の「-」を空扱い
        $brandName = ($data['brand_name'] ?? null);
        if ($brandName === '-' || $brandName === '−') {
            $brandName = null;
        }

        // 年齢を年代に変換（36 → 30、74 → 60）
        $age = $data['customer_age'] ?? null;
        $ageGroup = null;
        if ($age !== null && is_numeric($age)) {
            $ageInt = (int) $age;
            if ($ageInt < 20) {
                $ageGroup = '20';
            } elseif ($ageInt >= 60) {
                $ageGroup = '60';
            } else {
                $ageGroup = (string) (floor($ageInt / 10) * 10);
            }
        }

        // pawn-systemのカテゴリ名 → qr-reviewのカテゴリ名に変換
        $category = $this->mapCategory($data['category_name'] ?? null);

        // pawn-systemのshop_name → qr-reviewのstore_id候補として店舗名をそのまま返す
        return [
            'shop_name'      => $data['shop_name'] ?? null,
            'category'       => $category,
            'brand_name'     => $brandName,
            'product_name'   => $data['product_name'] ?? null,
            'model_number'   => $data['model_number'] ?? null,
            'feature'        => $data['feature'] ?? null,
            'product_status' => $data['product_status'] ?? null,
            'rank'           => $data['rank'] ?? null,
            'customer_name'  => $data['customer_name'] ?? null,
            'customer_kana'  => $data['customer_kana'] ?? null,
            'customer_age'   => $ageGroup,
        ];
    }

    /**
     * pawn-systemのカテゴリ名 → qr-reviewのカテゴリ名に変換
     */
    private function mapCategory(?string $pawnCategory): ?string
    {
        if (empty($pawnCategory)) {
            return null;
        }

        $map = [
            'ブランド品'    => 'ブランド品',
            '時計'         => '時計',
            '貴金属'       => '貴金属',
            'スマホ'       => 'スマホ・タブレット',
            'タブレット'    => 'スマホ・タブレット',
            'パソコン'      => '電化製品',
            '電化製品'      => '電化製品',
            '音響機器'      => '電化製品',
            '電動工具'      => '電動工具',
            'カメラ・レンズ' => 'カメラ・レンズ',
            'ゲーム・玩具'  => 'ゲーム・ソフト',
            'お酒'         => 'お酒',
            '楽器'         => '楽器',
            '金券類（非課税）' => '金券',
            '金券類（課税：切手・レターパック・印紙・金額のない株主優待券）' => '金券',
            '健康器具'      => '健康器具',
            '美容器具・健康器具・化粧品(未開封)' => '健康器具',
            'カー用品'      => 'カー用品',
            'スポーツ用品'  => '電動工具',
            '釣り用品'      => '電動工具',
            '衣類・靴'      => 'ブランド品',
        ];

        return $map[$pawnCategory] ?? null;
    }
}
