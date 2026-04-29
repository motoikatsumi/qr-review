<?php

namespace App\Services;

use App\Models\Store;
use App\Models\StoreWordPress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 店舗ごとに「FB/IG 連携用ブリッジ WordPress」を自動構築するサービス
 *
 * 流れ:
 *  1. WP コア zip をダウンロード（一度だけ→キャッシュ）
 *  2. 指定パスに展開
 *  3. wp-config.php を生成
 *  4. データベースを作成
 *  5. /wp-admin/install.php を HTTP で叩いて初期化
 *  6. プラグイン（Jetpack）を Application Password 経由で有効化
 *  7. App Password を発行して Laravel 側に保存（後で REST API 投稿用）
 */
class WordPressInstallerService
{
    private const WP_CORE_URL = 'https://ja.wordpress.org/latest-ja.zip';
    private const JETPACK_PLUGIN_SLUG = 'jetpack';
    private const WP_LOCALE = 'ja';

    private string $tenantWpRoot;
    private string $cacheDir;

    public function __construct()
    {
        // ローカル開発: /Applications/MAMP/htdocs/qr-tenant-wp/
        // 本番: 環境変数 TENANT_WP_ROOT で上書き可能
        $this->tenantWpRoot = env('TENANT_WP_ROOT', dirname(base_path()) . '/qr-tenant-wp');
        $this->cacheDir = storage_path('app/wp-installer-cache');

        if (!is_dir($this->tenantWpRoot)) {
            @mkdir($this->tenantWpRoot, 0755, true);
        }
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * 店舗のブリッジ WP をセットアップ
     */
    public function install(Store $store): StoreWordPress
    {
        $existing = StoreWordPress::where('store_id', $store->id)->first();
        if ($existing && $existing->isReady()) {
            return $existing;
        }

        // 既存のレコードを削除して新規作成（再インストール扱い）
        if ($existing) {
            $this->cleanupExisting($existing);
            $existing->delete();
        }

        $slug = Str::slug($store->slug ?? $store->name) . '-' . substr(md5($store->id), 0, 6);
        $installPath = $this->tenantWpRoot . '/' . $slug;
        $siteUrl = $this->buildSiteUrl($slug);

        // DB モード判定（create / shared / pool）
        $dbMode = strtolower(env('TENANT_WP_DB_MODE', 'create'));
        if (!in_array($dbMode, ['create', 'shared', 'pool'])) {
            $dbMode = 'create';
        }

        // モードごとに DB 名・テーブル接頭辞を決定
        $tablePrefix = 'wp_';
        switch ($dbMode) {
            case 'shared':
                // 単一 DB を全店舗で共有、テーブル接頭辞で分離
                $dbName = env('TENANT_WP_SHARED_DB_NAME', env('DB_DATABASE'));
                if (!$dbName) {
                    throw new \RuntimeException('TENANT_WP_SHARED_DB_NAME（または DB_DATABASE）が未設定です');
                }
                $tablePrefix = 'wp_qr_' . preg_replace('/[^a-z0-9_]/', '', strtolower($slug)) . '_';
                if (strlen($tablePrefix) > 40) $tablePrefix = substr($tablePrefix, 0, 40) . '_';
                break;

            case 'pool':
                // 事前作成された DB から空きを 1 つ割り当て
                $dbName = $this->allocateFromPool($store->id);
                break;

            case 'create':
            default:
                $dbName = 'qr_wp_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($slug));
                if (mb_strlen($dbName) > 60) $dbName = substr($dbName, 0, 60);
                break;
        }

        $adminUsername = 'qrreview_admin';
        $adminPassword = Str::random(20);
        $adminEmail = $store->notify_email ?: 'admin@example.com';

        $record = StoreWordPress::create([
            'store_id' => $store->id,
            'install_path' => $installPath,
            'site_url' => $siteUrl,
            'admin_url' => $siteUrl . '/wp-admin/',
            'db_name' => $dbName,
            'db_mode' => $dbMode,
            'table_prefix' => $tablePrefix,
            'admin_username' => $adminUsername,
            'admin_password_encrypted' => '',
            'admin_email' => $adminEmail,
            'status' => 'installing',
            'installed_plugins' => [],
            'connected_services' => [],
        ]);
        $record->admin_password = $adminPassword;
        $record->save();

        try {
            $this->step1_downloadWpCore();
            $this->step2_extractToPath($installPath);
            if ($dbMode === 'create') {
                $this->step3_createDatabase($dbName);
            }
            // shared / pool の場合は DB は既に存在するのでスキップ
            $this->step4_writeConfig($installPath, $dbName, $siteUrl, $tablePrefix);
            $this->writeBridgeMuPlugin($installPath);
            $this->step5_runInstaller($siteUrl, $store->name, $adminUsername, $adminPassword, $adminEmail);
            $this->step6_installJetpack($installPath);
            // REST API 認証に依存せず、DB 直接操作で完結させる
            $this->step7b_activatePluginViaDb($dbName, $tablePrefix, self::JETPACK_PLUGIN_SLUG);
            $apiToken = $this->step8b_writeApiTokenToDb($dbName, $tablePrefix);

            $record->app_password = $apiToken;
            $record->update([
                'status' => 'ready',
                'installed_plugins' => [self::JETPACK_PLUGIN_SLUG],
                'installed_at' => now(),
                'last_error' => null,
            ]);
            $record->save();
        } catch (\Throwable $e) {
            Log::error('WordPress installer failed', [
                'store_id' => $store->id,
                'step' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $record->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $record;
    }

    /**
     * 既存のインストールを削除（DB + ファイル）
     */
    public function uninstall(StoreWordPress $record): void
    {
        $this->cleanupExisting($record);
        $record->delete();
    }

    private function cleanupExisting(StoreWordPress $record): void
    {
        $mode = $record->db_mode ?? 'create';

        switch ($mode) {
            case 'create':
                try {
                    DB::statement("DROP DATABASE IF EXISTS `{$record->db_name}`");
                } catch (\Throwable $e) {
                    Log::warning('Failed to drop tenant WP database', ['db' => $record->db_name, 'error' => $e->getMessage()]);
                }
                break;

            case 'shared':
                // 該当店舗の WP テーブル群（接頭辞付き）だけ削除
                if ($record->table_prefix) {
                    try {
                        $rows = DB::select("SHOW TABLES LIKE ?", [$record->table_prefix . '%']);
                        foreach ($rows as $row) {
                            $tbl = array_values((array)$row)[0];
                            DB::statement("DROP TABLE IF EXISTS `{$tbl}`");
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to drop tenant WP tables', ['prefix' => $record->table_prefix, 'error' => $e->getMessage()]);
                    }
                }
                break;

            case 'pool':
                // テーブル群を削除（DB は残してプールに戻す）
                if ($record->table_prefix) {
                    try {
                        $rows = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name LIKE ?", [$record->db_name, $record->table_prefix . '%']);
                        foreach ($rows as $row) {
                            $tbl = $row->table_name ?? $row->TABLE_NAME ?? null;
                            if ($tbl) {
                                DB::statement("DROP TABLE IF EXISTS `{$record->db_name}`.`{$tbl}`");
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to drop pool tenant tables', ['db' => $record->db_name, 'error' => $e->getMessage()]);
                    }
                }
                $this->releaseToPool($record->db_name);
                break;
        }

        if (is_dir($record->install_path)) {
            $this->rmrf($record->install_path);
        }
    }

    /**
     * pool モード: 空き DB を 1 つ割り当てる
     */
    private function allocateFromPool(int $storeId): string
    {
        $row = DB::table('tenant_wp_db_pool')
            ->whereNull('store_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
        if (!$row) {
            throw new \RuntimeException('DB プールに空きがありません。管理画面の「DB プール管理」から DB を追加してください。');
        }
        DB::table('tenant_wp_db_pool')
            ->where('id', $row->id)
            ->update(['store_id' => $storeId, 'assigned_at' => now()]);
        return $row->db_name;
    }

    /**
     * pool モード: DB をプールに返却
     */
    private function releaseToPool(string $dbName): void
    {
        DB::table('tenant_wp_db_pool')
            ->where('db_name', $dbName)
            ->update(['store_id' => null, 'assigned_at' => null]);
    }

    /**
     * STEP 1: WP コア zip をキャッシュにダウンロード（既にあればスキップ）
     */
    private function step1_downloadWpCore(): string
    {
        $zipPath = $this->cacheDir . '/wordpress-ja-latest.zip';
        if (file_exists($zipPath) && filesize($zipPath) > 1024 * 1024) {
            return $zipPath;
        }

        $this->downloadFile(self::WP_CORE_URL, $zipPath, 'WordPress コア（日本語版）');
        return $zipPath;
    }

    /**
     * curl でダウンロード（HTTP リダイレクト + User-Agent 対応、Laravel HTTP クライアントの sink 経由だと
     * 大きな zip でリダイレクトが効かないケースがあるため）
     */
    private function downloadFile(string $url, string $dest, string $label): void
    {
        $ch = curl_init($url);
        $fp = fopen($dest, 'wb');
        if (!$fp) {
            throw new \RuntimeException("{$label}: 書き込み先を開けません: {$dest}");
        }
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 8,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; QRReview-WP-Installer/1.0)',
            CURLOPT_TIMEOUT        => 300,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $ok = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$ok || $httpCode >= 400) {
            @unlink($dest);
            throw new \RuntimeException("{$label} のダウンロードに失敗しました: HTTP {$httpCode} {$err}");
        }
        if (!file_exists($dest) || filesize($dest) < 1024 * 100) {
            throw new \RuntimeException("{$label} のサイズが小さすぎます（壊れている可能性）: " . filesize($dest) . ' bytes');
        }
    }

    /**
     * STEP 2: zip を指定パスに展開
     */
    private function step2_extractToPath(string $installPath): void
    {
        if (is_dir($installPath)) {
            $this->rmrf($installPath);
        }
        @mkdir($installPath, 0755, true);

        $zipPath = $this->cacheDir . '/wordpress-ja-latest.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('zip ファイルを開けません');
        }
        $tmpExtract = $this->cacheDir . '/_extract_' . uniqid();
        @mkdir($tmpExtract, 0755, true);
        if (!$zip->extractTo($tmpExtract)) {
            $zip->close();
            $this->rmrf($tmpExtract);
            throw new \RuntimeException('zip 展開に失敗しました');
        }
        $zip->close();

        // tmp/wordpress/* を installPath/ に移動
        $extractedRoot = $tmpExtract . '/wordpress';
        if (!is_dir($extractedRoot)) {
            $this->rmrf($tmpExtract);
            throw new \RuntimeException('展開後に wordpress/ ディレクトリが見つかりません');
        }
        foreach (scandir($extractedRoot) as $item) {
            if ($item === '.' || $item === '..') continue;
            rename($extractedRoot . '/' . $item, $installPath . '/' . $item);
        }
        $this->rmrf($tmpExtract);
    }

    /**
     * STEP 3: 専用データベースを作成
     */
    private function step3_createDatabase(string $dbName): void
    {
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * STEP 4: wp-config.php を生成
     */
    private function step4_writeConfig(string $installPath, string $dbName, string $siteUrl, string $tablePrefix = 'wp_'): void
    {
        $sample = $installPath . '/wp-config-sample.php';
        $config = $installPath . '/wp-config.php';
        if (!file_exists($sample)) {
            throw new \RuntimeException('wp-config-sample.php が見つかりません');
        }

        $content = file_get_contents($sample);
        $dbHost = env('DB_HOST', '127.0.0.1') . ':' . env('DB_PORT', '3306');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');

        $content = str_replace(
            ['database_name_here', 'username_here', 'password_here', 'localhost'],
            [$dbName, $dbUser, $dbPass, $dbHost],
            $content
        );

        // テーブル接頭辞の差し替え（shared / pool モードで店舗ごとに分離）
        $content = preg_replace(
            '/\$table_prefix\s*=\s*[\'"][^\'"]+[\'"]\s*;/',
            "\$table_prefix = '" . addslashes($tablePrefix) . "';",
            $content
        );

        // ユニークキーを生成
        $keys = ['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'];
        foreach ($keys as $key) {
            $val = bin2hex(random_bytes(32));
            $content = preg_replace(
                "/define\(\s*'{$key}',\s*'put your unique phrase here'\s*\);/",
                "define('{$key}', '{$val}');",
                $content
            );
        }

        // テーブル接頭辞・noindex・日本語ロケール設定追加
        $extraConfig = "\n// QRレビュー専用設定\n"
            . "define('WPLANG', '" . self::WP_LOCALE . "');\n"
            . "define('WP_AUTO_UPDATE_CORE', 'minor');\n"
            . "define('DISABLE_WP_CRON', false);\n"
            . "define('WP_HOME', '{$siteUrl}');\n"
            . "define('WP_SITEURL', '{$siteUrl}');\n";

        $content = str_replace("/* That's all, stop editing!", $extraConfig . "/* That's all, stop editing!", $content);

        file_put_contents($config, $content);
    }

    /**
     * STEP 5: WP インストーラを実行（HTTP）
     */
    private function step5_runInstaller(string $siteUrl, string $siteName, string $adminUser, string $adminPass, string $adminEmail): void
    {
        $installUrl = $siteUrl . '/wp-admin/install.php?step=2';
        $resp = Http::timeout(60)->asForm()->post($installUrl, [
            'weblog_title' => $siteName . ' - 連携ブリッジ',
            'user_name' => $adminUser,
            'admin_password' => $adminPass,
            'admin_password2' => $adminPass,
            'pw_weak' => '1',
            'admin_email' => $adminEmail,
            'blog_public' => '0', // 検索エンジンインデックス禁止（noindex）
            'language' => self::WP_LOCALE, // ja: 日本語版インストール
            'Submit' => 'WordPress をインストール',
        ]);

        if (!$resp->successful()) {
            throw new \RuntimeException('WP インストーラ実行失敗: HTTP ' . $resp->status() . ' / ' . substr($resp->body(), 0, 200));
        }
        // 「成功」または「すでにインストール済み」を検出
        if (!str_contains($resp->body(), 'success') && !str_contains($resp->body(), 'インストール') && !str_contains($resp->body(), 'Installation')) {
            throw new \RuntimeException('WP インストーラ応答が想定と異なります: ' . substr(strip_tags($resp->body()), 0, 300));
        }
    }

    /**
     * STEP 6: Jetpack プラグインを wp-content/plugins に直接展開
     * （Application Password がまだ無いため REST API は使えない → ファイルシステム直接展開）
     */
    private function step6_installJetpack(string $installPath): void
    {
        $pluginUrl = 'https://downloads.wordpress.org/plugin/' . self::JETPACK_PLUGIN_SLUG . '.latest-stable.zip';
        $zipPath = $this->cacheDir . '/' . self::JETPACK_PLUGIN_SLUG . '.zip';

        if (!file_exists($zipPath) || filesize($zipPath) < 100 * 1024) {
            $this->downloadFile($pluginUrl, $zipPath, 'Jetpack プラグイン');
        }

        $pluginsDir = $installPath . '/wp-content/plugins';
        if (!is_dir($pluginsDir)) {
            throw new \RuntimeException('plugins ディレクトリが見つかりません');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Jetpack zip を開けません');
        }
        if (!$zip->extractTo($pluginsDir)) {
            $zip->close();
            throw new \RuntimeException('Jetpack 展開失敗');
        }
        $zip->close();

        // 注: 有効化は customer 自身が wp-admin から行う or 後続実装で REST API 経由で行う
    }

    /**
     * QRレビューブリッジ用 mu-plugin を配置
     * - HTTP 環境でも Application Password を有効化
     * - Apache が削ぎ落とした Authorization ヘッダを復元（PHP_AUTH_USER/PW へ展開）
     */
    private function writeBridgeMuPlugin(string $installPath): void
    {
        $muDir = $installPath . '/wp-content/mu-plugins';
        if (!is_dir($muDir)) {
            @mkdir($muDir, 0755, true);
        }
        $muFile = $muDir . '/qrreview-bridge.php';
        $code = <<<'PHP'
<?php
/**
 * Plugin Name: QRレビュー連携ブリッジ
 * Description: QRレビューから本 WP を制御するための補助設定（自動配置 mu-plugin）
 * Version: 1.0
 */

// ============================================================
// 1. Authorization ヘッダの復元（Apache CGI/FastCGI 対策）
//    ファイル読込時に即実行（フックを使わない）— REST API 認証より前に必ず通す
// ============================================================
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    $authHeader = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (is_array($headers)) {
            foreach ($headers as $k => $v) {
                if (strcasecmp($k, 'Authorization') === 0) {
                    $authHeader = $v;
                    break;
                }
            }
        }
    } elseif (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $k => $v) {
                if (strcasecmp($k, 'Authorization') === 0) {
                    $authHeader = $v;
                    break;
                }
            }
        }
    }
    if ($authHeader && stripos($authHeader, 'Basic ') === 0) {
        $decoded = base64_decode(substr($authHeader, 6));
        if (strpos($decoded, ':') !== false) {
            list($user, $pass) = explode(':', $decoded, 2);
            $_SERVER['PHP_AUTH_USER'] = $user;
            $_SERVER['PHP_AUTH_PW'] = $pass;
        }
    }
}

// ============================================================
// 2. HTTP 環境でも Application Password を許可
// ============================================================
add_filter('wp_is_application_passwords_available', '__return_true');

// ============================================================
// 3. 検索エンジンインデックス禁止を強制
// ============================================================
add_filter('pre_option_blog_public', function () { return '0'; });

// ============================================================
// 4. 独自 Bearer トークン認証
//    Apache が Authorization ヘッダを通さない環境用に、
//    複数のヘッダ取得経路を試して Bearer トークンを抽出
// ============================================================
function qrreview_get_bearer_token() {
    $auth = '';
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $h = apache_request_headers();
        if (is_array($h)) {
            foreach ($h as $k => $v) if (strcasecmp($k, 'Authorization') === 0) { $auth = $v; break; }
        }
    } elseif (function_exists('getallheaders')) {
        $h = getallheaders();
        if (is_array($h)) {
            foreach ($h as $k => $v) if (strcasecmp($k, 'Authorization') === 0) { $auth = $v; break; }
        }
    }
    // クエリパラメータでも受け付ける（Apache が完全に Authorization を剥ぐ環境のフォールバック）
    if (!$auth && !empty($_GET['qr_token'])) {
        return $_GET['qr_token'];
    }
    if (stripos($auth, 'Bearer ') === 0) {
        return trim(substr($auth, 7));
    }
    return null;
}

add_filter('determine_current_user', function ($user_id) {
    if (!empty($user_id)) return $user_id;
    $token = qrreview_get_bearer_token();
    if (!$token) return $user_id;
    $expected = get_option('qrreview_api_token');
    if (!$expected || !hash_equals($expected, $token)) return $user_id;
    $admins = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID']);
    return !empty($admins) ? (int) $admins[0]->ID : $user_id;
}, 99);

// ============================================================
// 5. カスタム REST エンドポイント（QRレビューから呼ぶ）
// ============================================================
add_action('rest_api_init', function () {
    // 接続状況取得（Jetpack 接続 + Publicize 連携）
    register_rest_route('qrreview/v1', '/status', [
        'methods'  => 'GET',
        'permission_callback' => function () {
            $token = qrreview_get_bearer_token();
            $expected = get_option('qrreview_api_token');
            return $token && $expected && hash_equals($expected, $token);
        },
        'callback' => function () {
            $jetpackActive = in_array('jetpack/jetpack.php', (array) get_option('active_plugins', []));
            $jetpackConnected = false;
            $connections = [];

            if ($jetpackActive && class_exists('Automattic\\Jetpack\\Connection\\Manager')) {
                try {
                    $mgr = new Automattic\Jetpack\Connection\Manager();
                    $jetpackConnected = $mgr->is_connected();
                } catch (\Throwable $e) {}
            }

            // Publicize 連携情報（オプションから読む）
            $opt = get_option('jetpack_publicize_connections', []);
            if (is_array($opt)) {
                foreach ($opt as $service => $entries) {
                    if (!is_array($entries)) continue;
                    foreach ($entries as $entry) {
                        $meta = $entry['connection_data']['meta'] ?? [];
                        $connections[] = [
                            'service'      => $service,
                            'display_name' => $meta['display_name'] ?? ($entry['connection_data']['external_display'] ?? ''),
                            'profile_url'  => $meta['profile_url'] ?? '',
                        ];
                    }
                }
            }

            return [
                'jetpack_active'    => $jetpackActive,
                'jetpack_connected' => $jetpackConnected,
                'connections'       => $connections,
            ];
        },
    ]);
});
PHP;
        file_put_contents($muFile, $code);

        // Apache の Authorization ヘッダが PHP に届くよう .htaccess に追記
        $this->ensureHtaccessAuthorizationPassthrough($installPath);
    }

    /**
     * .htaccess に Authorization ヘッダ パススルーを追記
     */
    private function ensureHtaccessAuthorizationPassthrough(string $installPath): void
    {
        $htaccess = $installPath . '/.htaccess';
        $marker = '# QRReview Bridge: pass Authorization header';
        $rule = <<<'HTACCESS'

# QRReview Bridge: pass Authorization header
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP:Authorization} ^(.+)$
RewriteRule .* - [E=HTTP_AUTHORIZATION:%1]
</IfModule>
<IfModule mod_setenvif.c>
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
</IfModule>

HTACCESS;

        $existing = file_exists($htaccess) ? file_get_contents($htaccess) : '';
        if (strpos($existing, $marker) === false) {
            file_put_contents($htaccess, $existing . $rule);
        }
    }

    /**
     * STEP 7b: プラグインを DB 直接操作で有効化
     * REST API 認証に依存せず、wp_options.active_plugins を直接更新する
     */
    private function step7b_activatePluginViaDb(string $dbName, string $prefix, string $pluginSlug): void
    {
        $pluginFile = $pluginSlug . '/' . $pluginSlug . '.php';
        $serialized = serialize([$pluginFile]);

        $rows = DB::select("SELECT option_value FROM `{$dbName}`.`{$prefix}options` WHERE option_name = 'active_plugins' LIMIT 1");
        if (empty($rows)) {
            DB::statement(
                "INSERT INTO `{$dbName}`.`{$prefix}options` (option_name, option_value, autoload) VALUES (?, ?, 'yes')",
                ['active_plugins', $serialized]
            );
        } else {
            $current = @unserialize($rows[0]->option_value) ?: [];
            if (!in_array($pluginFile, $current)) {
                $current[] = $pluginFile;
                DB::statement(
                    "UPDATE `{$dbName}`.`{$prefix}options` SET option_value = ? WHERE option_name = 'active_plugins'",
                    [serialize($current)]
                );
            }
        }
    }

    /**
     * STEP 8b: 独自 API トークンを DB に書き込む
     * mu-plugin がこのトークンを Bearer 認証として受け付ける
     */
    private function step8b_writeApiTokenToDb(string $dbName, string $prefix): string
    {
        $token = bin2hex(random_bytes(32));

        $exists = DB::select("SELECT 1 FROM `{$dbName}`.`{$prefix}options` WHERE option_name = 'qrreview_api_token' LIMIT 1");
        if (empty($exists)) {
            DB::statement(
                "INSERT INTO `{$dbName}`.`{$prefix}options` (option_name, option_value, autoload) VALUES (?, ?, 'yes')",
                ['qrreview_api_token', $token]
            );
        } else {
            DB::statement(
                "UPDATE `{$dbName}`.`{$prefix}options` SET option_value = ? WHERE option_name = 'qrreview_api_token'",
                [$token]
            );
        }
        return $token;
    }

    /**
     * STEP 7: プラグインを有効化（WP REST API）
     */
    private function step7_activatePlugin(string $siteUrl, string $adminUser, string $adminPass, string $pluginSlug): void
    {
        $endpoint = $siteUrl . '/wp-json/wp/v2/plugins/' . $pluginSlug . '/' . $pluginSlug;
        $resp = Http::timeout(30)
            ->withBasicAuth($adminUser, $adminPass)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->put($endpoint, ['status' => 'active']);

        if (!$resp->successful()) {
            // REST API での有効化失敗時は active_plugins option を直接更新（フォールバック）
            Log::warning('Plugin REST activate failed, falling back to direct DB update', [
                'plugin' => $pluginSlug,
                'status' => $resp->status(),
                'body' => substr($resp->body(), 0, 200),
            ]);
            $this->fallbackActivatePluginViaDb($pluginSlug);
        }
    }

    /**
     * REST 失敗時のフォールバック：active_plugins option を直接更新
     * 注: tenant WP の DB 名は store_wordpress テーブルから引いて該当 DB に書き込む必要があるが、
     *     現在のメソッドシグネチャでは取得できないため、呼び出し元から渡す形に後で改修可。
     *     ここでは何もせず Log のみ出す（手動回避でカバー）
     */
    private function fallbackActivatePluginViaDb(string $pluginSlug): void
    {
        // 後続の改善ポイント：DB に直接書き込む必要がある場合は別オーバーロードを追加
        Log::info('Plugin activation requires manual step', ['plugin' => $pluginSlug]);
    }

    /**
     * STEP 8: Application Password を発行（後続の REST API 呼出に使用）
     */
    private function step8_createAppPassword(string $siteUrl, string $adminUser, string $adminPass): string
    {
        $endpoint = $siteUrl . '/wp-json/wp/v2/users/me/application-passwords';
        $resp = Http::timeout(30)
            ->withBasicAuth($adminUser, $adminPass)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($endpoint, ['name' => 'QRレビュー連携 (' . now()->format('Y-m-d') . ')']);

        if (!$resp->successful()) {
            throw new \RuntimeException(
                'Application Password 発行失敗: HTTP ' . $resp->status() . ' / ' . substr($resp->body(), 0, 200)
            );
        }
        $password = $resp->json('password');
        if (!$password) {
            throw new \RuntimeException('Application Password レスポンスに password が含まれていません');
        }
        return $password;
    }

    /**
     * URL ビルダー（環境ごとに切替可）
     */
    private function buildSiteUrl(string $slug): string
    {
        $base = env('TENANT_WP_BASE_URL', 'http://127.0.0.1:8888/qr-tenant-wp');
        return rtrim($base, '/') . '/' . $slug;
    }

    /**
     * 再帰削除
     */
    private function rmrf(string $path): void
    {
        if (!file_exists($path)) return;
        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') continue;
            $this->rmrf($path . '/' . $item);
        }
        @rmdir($path);
    }
}
