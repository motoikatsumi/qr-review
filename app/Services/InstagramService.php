<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    protected string $baseUrl = 'https://graph.instagram.com/v25.0';
    protected ?string $accessToken;
    protected ?string $igUserId;

    public function __construct()
    {
        $this->accessToken = SiteSetting::get('instagram_access_token');
        $this->igUserId = SiteSetting::get('instagram_user_id');
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
        // Step 1: メディアコンテナ作成
        // v22以降、graph.instagram.com の /media エンドポイントは media_type の明示指定が必須
        $containerResponse = Http::asForm()->post("{$this->baseUrl}/{$this->igUserId}/media", [
            'media_type' => 'IMAGE',
            'image_url' => $imageUrl,
            'caption' => $caption,
            'access_token' => $this->accessToken,
        ]);

        if (!$containerResponse->successful()) {
            $error = $containerResponse->json('error.message', 'メディアコンテナの作成に失敗しました');
            Log::error('Instagram container creation failed', [
                'status' => $containerResponse->status(),
                'response' => $containerResponse->json(),
            ]);
            return ['success' => false, 'media_id' => null, 'error' => $error];
        }

        $containerId = $containerResponse->json('id');
        if (!$containerId) {
            return ['success' => false, 'media_id' => null, 'error' => 'コンテナIDが取得できませんでした'];
        }

        // Step 2: コンテナの処理完了を待つ（最大30秒）
        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(3);
            $statusResponse = Http::get("{$this->baseUrl}/{$containerId}", [
                'fields' => 'status_code',
                'access_token' => $this->accessToken,
            ]);
            $statusCode = $statusResponse->json('status_code');
            if ($statusCode === 'FINISHED') {
                break;
            }
            if ($statusCode === 'ERROR') {
                return ['success' => false, 'media_id' => null, 'error' => 'メディアコンテナの処理に失敗しました'];
            }
        }

        // Step 3: メディア公開
        $publishResponse = Http::asForm()->post("{$this->baseUrl}/{$this->igUserId}/media_publish", [
            'creation_id' => $containerId,
            'access_token' => $this->accessToken,
        ]);

        if (!$publishResponse->successful()) {
            $error = $publishResponse->json('error.message', 'メディアの公開に失敗しました');
            Log::error('Instagram media publish failed', [
                'status' => $publishResponse->status(),
                'response' => $publishResponse->json(),
            ]);
            return ['success' => false, 'media_id' => null, 'error' => $error];
        }

        $mediaId = $publishResponse->json('id');
        return ['success' => true, 'media_id' => $mediaId, 'error' => null];
    }
}
