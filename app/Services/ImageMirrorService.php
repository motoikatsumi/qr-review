<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudinary に画像をアップロードして公開 URL を返すサービス。
 * Sakura/Lolipop の WAF が Meta クローラを弾く問題を回避する。
 */
class ImageMirrorService
{
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;

    public function __construct()
    {
        $this->cloudName = env('CLOUDINARY_CLOUD_NAME', '');
        $this->apiKey    = env('CLOUDINARY_API_KEY', '');
        $this->apiSecret = env('CLOUDINARY_API_SECRET', '');
    }

    /**
     * 元画像 URL を Cloudinary にアップロード。Cloudinary の URL を返す。
     */
    public function mirror(string $sourceUrl): ?string
    {
        if (!$this->cloudName || !$this->apiKey || !$this->apiSecret) {
            Log::error('Cloudinary credentials not configured');
            return null;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'cmirror_');
        $ch = curl_init($sourceUrl);
        $fp = fopen($tmpFile, 'wb');
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; QRBridge/1.0)',
            CURLOPT_FAILONERROR    => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $ok = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if (!$ok || $code >= 400 || filesize($tmpFile) < 1024) {
            @unlink($tmpFile);
            Log::error('Source image download failed', ['url' => $sourceUrl, 'http' => $code]);
            return null;
        }

        $publicId  = 'qr-mirror/' . date('Ymd') . '/' . bin2hex(random_bytes(8));
        $timestamp = time();
        $signString = "public_id=$publicId&timestamp=$timestamp" . $this->apiSecret;
        $signature = sha1($signString);

        $resp = Http::timeout(60)
            ->attach('file', fopen($tmpFile, 'r'), basename($tmpFile))
            ->post("https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload", [
                'api_key'   => $this->apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'public_id' => $publicId,
            ]);

        @unlink($tmpFile);

        if (!$resp->successful() || !$resp->json('secure_url')) {
            Log::error('Cloudinary upload failed', ['body' => $resp->body()]);
            return null;
        }

        return $resp->json('secure_url');
    }

    /**
     * ローカルファイルを Cloudinary にアップロードして公開 URL を返す。
     * WordPress を経由しない投稿フロー用。
     */
    public function mirrorFromLocal(string $localPath): ?string
    {
        if (!$this->cloudName || !$this->apiKey || !$this->apiSecret) {
            Log::error('Cloudinary credentials not configured');
            return null;
        }
        if (!file_exists($localPath) || filesize($localPath) < 1024) {
            Log::error('Local file not found or too small', ['path' => $localPath]);
            return null;
        }

        $publicId  = 'qr-mirror/' . date('Ymd') . '/' . bin2hex(random_bytes(8));
        $timestamp = time();
        $signString = "public_id=$publicId&timestamp=$timestamp" . $this->apiSecret;
        $signature = sha1($signString);

        $resp = Http::timeout(60)
            ->attach('file', fopen($localPath, 'r'), basename($localPath))
            ->post("https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload", [
                'api_key'   => $this->apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'public_id' => $publicId,
            ]);

        if (!$resp->successful() || !$resp->json('secure_url')) {
            Log::error('Cloudinary upload from local failed', ['body' => $resp->body()]);
            return null;
        }

        return $resp->json('secure_url');
    }

    /**
     * Cloudinary にアップロードしたファイルを削除（投稿後の後片付け）
     */
    public function delete(string $cloudinaryUrl): void
    {
        if (!preg_match('#/v\d+/(.+)\.[^./]+$#', $cloudinaryUrl, $m)) return;
        $publicId = $m[1];

        $timestamp = time();
        $signString = "public_id=$publicId&timestamp=$timestamp" . $this->apiSecret;
        $signature = sha1($signString);

        Http::asForm()->post(
            "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/destroy",
            [
                'api_key'   => $this->apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'public_id' => $publicId,
            ]
        );
    }

    public function cleanupOld(int $minutes = 60): int
    {
        return 0;
    }
}
