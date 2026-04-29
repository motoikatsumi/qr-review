<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressService
{
    protected string $baseUrl;
    protected string $username;
    protected string $appPassword;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.wordpress.url', ''), '/');
        $this->username = config('services.wordpress.username', '');
        $this->appPassword = config('services.wordpress.app_password', '');
    }

    /**
     * 店舗ごとの設定でインスタンス生成（store_integrations優先、なければconfig）
     */
    public static function forStore(Store $store): self
    {
        $instance = new self();
        $integration = $store->integration('wordpress');
        if ($integration && $integration->is_active) {
            $extra = $integration->extra_data ?? [];
            $instance->baseUrl     = rtrim($extra['wp_url'] ?? '', '/');
            $instance->username    = $extra['wp_username'] ?? '';
            $instance->appPassword = $integration->access_token ?? '';
        }
        return $instance;
    }

    /**
     * 外部から認証情報をセット（接続テスト用）
     */
    public function setCredentials(string $baseUrl, string $username, string $appPassword): void
    {
        $this->baseUrl     = $baseUrl;
        $this->username    = $username;
        $this->appPassword = $appPassword;
    }

    protected function api()
    {
        return Http::withBasicAuth($this->username, $this->appPassword)
            ->timeout(30);
    }

    /**
     * WordPress REST API が接続可能か確認
     */
    public function testConnection(): array
    {
        try {
            $response = $this->api()->get($this->baseUrl . '/wp-json/wp/v2/users/me');
            if ($response->successful()) {
                return ['success' => true, 'user' => $response->json('name')];
            }
            return ['success' => false, 'error' => 'HTTP ' . $response->status() . ': ' . $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * カテゴリ一覧取得
     */
    public function getCategories(): array
    {
        try {
            $categories = [];
            $page = 1;
            do {
                $response = $this->api()->get($this->baseUrl . '/wp-json/wp/v2/categories', [
                    'per_page' => 100,
                    'page' => $page,
                ]);
                if (!$response->successful()) break;
                $data = $response->json();
                if (empty($data)) break;
                $categories = array_merge($categories, $data);
                $page++;
            } while (count($data) === 100);

            return $categories;
        } catch (\Exception $e) {
            Log::error('WordPress: getCategories failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * タグ一覧取得
     */
    public function getTags(): array
    {
        try {
            $tags = [];
            $page = 1;
            do {
                $response = $this->api()->get($this->baseUrl . '/wp-json/wp/v2/tags', [
                    'per_page' => 100,
                    'page' => $page,
                ]);
                if (!$response->successful()) break;
                $data = $response->json();
                if (empty($data)) break;
                $tags = array_merge($tags, $data);
                $page++;
            } while (count($data) === 100);

            return $tags;
        } catch (\Exception $e) {
            Log::error('WordPress: getTags failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * タグをスラッグまたは名前で検索・なければ作成
     */
    public function findOrCreateTag(string $name): ?int
    {
        try {
            $response = $this->api()->get($this->baseUrl . '/wp-json/wp/v2/tags', [
                'search' => $name,
                'per_page' => 10,
            ]);
            if ($response->successful()) {
                foreach ($response->json() as $tag) {
                    if (mb_strtolower($tag['name']) === mb_strtolower($name)) {
                        return $tag['id'];
                    }
                }
            }

            // 見つからなければ作成
            $response = $this->api()->post($this->baseUrl . '/wp-json/wp/v2/tags', [
                'name' => $name,
            ]);
            if ($response->successful()) {
                return $response->json('id');
            }

            Log::error('WordPress: findOrCreateTag failed', ['name' => $name, 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('WordPress: findOrCreateTag exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * カテゴリをスラッグで検索
     */
    public function findCategoryBySlug(string $slug): ?int
    {
        try {
            $response = $this->api()->get($this->baseUrl . '/wp-json/wp/v2/categories', [
                'slug' => $slug,
            ]);
            if ($response->successful() && !empty($response->json())) {
                return $response->json()[0]['id'];
            }
            return null;
        } catch (\Exception $e) {
            Log::error('WordPress: findCategoryBySlug exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * メディア（画像）をアップロード
     * @return array|null 成功時は['id'=>..., 'url'=>...]、失敗時はnull
     * @throws \RuntimeException 詳細なエラーメッセージ付き
     */
    public function uploadMedia(string $filePath, string $fileName): ?array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('画像ファイルが見つかりません: ' . $filePath);
        }

        $mimeType = mime_content_type($filePath);

        // ファイル名をASCII安全な形式に変換
        $safeFileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

        $response = $this->api()
            ->withHeaders([
                'Content-Disposition' => 'attachment; filename="' . $safeFileName . '"',
                'Content-Type' => $mimeType,
            ])
            ->withBody(file_get_contents($filePath), $mimeType)
            ->post($this->baseUrl . '/wp-json/wp/v2/media');

        if ($response->successful()) {
            $data = $response->json();
            return [
                'id' => $data['id'],
                'url' => $data['source_url'] ?? $data['guid']['rendered'] ?? '',
            ];
        }

        $errorBody = $response->body();
        $errorJson = $response->json();
        $errorMessage = $errorJson['message'] ?? $errorBody;
        Log::error('WordPress: uploadMedia failed', ['status' => $response->status(), 'body' => $errorBody]);
        throw new \RuntimeException('WordPress画像アップロード失敗 (HTTP ' . $response->status() . '): ' . $errorMessage);
    }

    /**
     * 投稿を作成
     * @throws \RuntimeException
     */
    public function createPost(array $data): ?array
    {
        try {
            $postData = [
                'title' => $data['title'],
                'content' => $data['content'],
                'status' => 'publish',
            ];

            if (!empty($data['categories'])) {
                $postData['categories'] = $data['categories'];
            }
            if (!empty($data['tags'])) {
                $postData['tags'] = $data['tags'];
            }
            if (!empty($data['featured_media'])) {
                $postData['featured_media'] = $data['featured_media'];
            }
            if (!empty($data['meta'])) {
                $postData['meta'] = $data['meta'];
            }

            $response = $this->api()->post($this->baseUrl . '/wp-json/wp/v2/posts', $postData);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'id' => $result['id'],
                    'link' => $result['link'] ?? '',
                ];
            }

            Log::error('WordPress: createPost failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('WordPress: createPost exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 投稿を更新
     */
    public function updatePost(int $postId, array $data): bool
    {
        try {
            $response = $this->api()->post($this->baseUrl . '/wp-json/wp/v2/posts/' . $postId, $data);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WordPress: updatePost exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 投稿を削除
     */
    public function deletePost(int $postId): bool
    {
        try {
            $response = $this->api()->delete($this->baseUrl . '/wp-json/wp/v2/posts/' . $postId, [
                'force' => true,
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WordPress: deletePost exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * メディアを削除
     */
    public function deleteMedia(int $mediaId): bool
    {
        try {
            $response = $this->api()->delete($this->baseUrl . '/wp-json/wp/v2/media/' . $mediaId, [
                'force' => true,
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WordPress: deleteMedia exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
