<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookService
{
    protected string $baseUrl = 'https://graph.facebook.com/v25.0';
    protected ?string $accessToken;
    protected ?string $pageId;

    public function __construct()
    {
        $this->accessToken = SiteSetting::get('facebook_page_access_token');
        $this->pageId = SiteSetting::get('facebook_page_id');
    }

    /**
     * Facebook連携が有効か確認
     */
    public function isConnected(): bool
    {
        return !empty($this->accessToken) && !empty($this->pageId);
    }

    /**
     * 画像付き投稿を公開
     *
     * @param string $imageUrl 公開URLの画像（WordPress画像URL）
     * @param string $message 投稿テキスト
     * @return array ['success' => bool, 'post_id' => string|null, 'error' => string|null]
     */
    public function publishPost(string $imageUrl, string $message): array
    {
        $response = Http::post("{$this->baseUrl}/{$this->pageId}/photos", [
            'url' => $imageUrl,
            'message' => $message,
            'access_token' => $this->accessToken,
        ]);

        if (!$response->successful()) {
            $error = $response->json('error.message', 'Facebook投稿の作成に失敗しました');
            Log::error('Facebook post creation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return ['success' => false, 'post_id' => null, 'error' => $error];
        }

        $postId = $response->json('post_id') ?? $response->json('id');
        if (!$postId) {
            return ['success' => false, 'post_id' => null, 'error' => '投稿IDが取得できませんでした'];
        }

        return ['success' => true, 'post_id' => $postId, 'error' => null];
    }

    /**
     * Facebook投稿を削除
     *
     * @param string $postId Facebook投稿ID
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function deletePost(string $postId): array
    {
        $response = Http::delete("{$this->baseUrl}/{$postId}", [
            'access_token' => $this->accessToken,
        ]);

        if (!$response->successful()) {
            $error = $response->json('error.message', 'Facebook投稿の削除に失敗しました');
            Log::error('Facebook post delete failed', [
                'post_id' => $postId,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return ['success' => false, 'error' => $error];
        }

        return ['success' => true, 'error' => null];
    }
}
