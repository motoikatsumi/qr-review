<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Instagram 投稿 (Facebook Graph API 経由)
 *
 * Instagram Business Login (graph.instagram.com) は Use Case 制約で
 * content_publish 権限の取得が実質不可能なため、Facebook Page Access Token
 * + Instagram Business Account ID を用いて graph.facebook.com 経由で投稿する。
 *
 * store_integrations への保存例:
 *   service: 'instagram'
 *     access_token: Facebook Page Access Token (長期)
 *     extra_data: { ig_user_id: Instagram Business Account ID }
 */
class InstagramService
{
    protected string $baseUrl = 'https://graph.facebook.com/v25.0';
    protected ?string $accessToken = null;
    protected ?string $igUserId = null;

    public function __construct()
    {
        // forStore() で店舗ごとのトークンをセットして使用
    }

    /**
     * 店舗ごとの設定でインスタンス生成
     */
    public static function forStore(Store $store): self
    {
        $instance = new self();
        $integration = $store->integration('instagram');
        if ($integration && $integration->is_active) {
            $instance->accessToken = $integration->access_token;
            $instance->igUserId    = $integration->extra_data['ig_user_id'] ?? null;
        }
        return $instance;
    }

    /**
     * Instagram連携が有効か確認
     */
    public function isConnected(): bool
    {
        return !empty($this->accessToken) && !empty($this->igUserId);
    }

    /**
     * 画像付き投稿を公開
     *
     * @param string $imageUrl 公開URLの画像（WordPress画像URL）
     * @param string $caption 投稿テキスト
     * @return array ['success' => bool, 'media_id' => string|null, 'error' => string|null]
     */
    public function publishPost(string $imageUrl, string $caption): array
    {
        // Cloudinary mirror to bypass Sakura/Lolipop WAF for Meta crawlers
        if ($__m = (new \App\Services\ImageMirrorService())->mirror($imageUrl)) { $imageUrl = $__m; }

        // Step 1: メディアコンテナ作成
        $containerResponse = Http::asForm()->post("{$this->baseUrl}/{$this->igUserId}/media", [
            'image_url'    => $imageUrl,
            'caption'      => $caption,
            'access_token' => $this->accessToken,
        ]);

        if (!$containerResponse->successful()) {
            $body = $containerResponse->json();
            $error = data_get($body, 'error.message', 'メディアコンテナの作成に失敗しました');
            Log::error('Instagram container creation failed', [
                'status'    => $containerResponse->status(),
                'response'  => $body,
                'image_url' => $imageUrl,
            ]);
            return ['success' => false, 'media_id' => null, 'error' => $error];
        }

        $containerId = $containerResponse->json('id');
        if (!$containerId) {
            return ['success' => false, 'media_id' => null, 'error' => 'コンテナIDが取得できませんでした'];
        }

        // Step 2: コンテナの処理完了を待つ（最大30秒）
        $maxAttempts = 10;
        $lastStatus = null;
        $lastStatusBody = null;
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(3);
            $statusResponse = Http::get("{$this->baseUrl}/{$containerId}", [
                'fields'       => 'status_code,status',
                'access_token' => $this->accessToken,
            ]);
            $lastStatusBody = $statusResponse->json();
            $lastStatus = $statusResponse->json('status_code');
            if ($lastStatus === 'FINISHED') {
                break;
            }
            if ($lastStatus === 'ERROR') {
                Log::error('Instagram container processing error', [
                    'container_id' => $containerId,
                    'response'     => $lastStatusBody,
                ]);
                $detail = data_get($lastStatusBody, 'status') ?: 'メディアコンテナの処理に失敗しました';
                return ['success' => false, 'media_id' => null, 'error' => 'Instagramメディア処理失敗: ' . $detail];
            }
        }

        if ($lastStatus !== 'FINISHED') {
            Log::warning('Instagram container did not finish in time', [
                'container_id' => $containerId,
                'last_status'  => $lastStatus,
                'response'     => $lastStatusBody,
            ]);
            return ['success' => false, 'media_id' => null, 'error' => 'Instagramメディア処理がタイムアウトしました'];
        }

        // Step 3: メディア公開
        $publishResponse = Http::asForm()->post("{$this->baseUrl}/{$this->igUserId}/media_publish", [
            'creation_id'  => $containerId,
            'access_token' => $this->accessToken,
        ]);

        if (!$publishResponse->successful()) {
            $error = $publishResponse->json('error.message', 'メディアの公開に失敗しました');
            Log::error('Instagram media publish failed', [
                'status'   => $publishResponse->status(),
                'response' => $publishResponse->json(),
            ]);
            return ['success' => false, 'media_id' => null, 'error' => $error];
        }

        $mediaId = $publishResponse->json('id');
        return ['success' => true, 'media_id' => $mediaId, 'error' => null];
    }

    /**
     * アクセストークンの接続テスト（外部から呼び出し可能）
     *
     * @param string $accessToken Facebook Page Access Token
     * @param string $igUserId Instagram Business Account ID
     */
    public function testToken(string $accessToken, string $igUserId): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/{$igUserId}", [
                'fields'       => 'id,username,name',
                'access_token' => $accessToken,
            ]);
            if ($response->successful()) {
                return [
                    'success'  => true,
                    'name'     => $response->json('name'),
                    'username' => $response->json('username'),
                ];
            }
            return ['success' => false, 'error' => $response->json('error.message', 'HTTP ' . $response->status())];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
