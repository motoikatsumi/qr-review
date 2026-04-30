<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

/**
 * ローカル(APP_ENV=local)でだけ動作する開発支援コマンド。
 * 指定 admin ユーザーのセッションをセットアップし、Playwright 等の
 * スクリーンショット自動化ツールに渡すための laravel_session 値を出力する。
 *
 *   php artisan local:auth-cookie info@assist-grp.jp
 */
class LocalAuthCookie extends Command
{
    protected $signature = 'local:auth-cookie {email}';

    protected $description = '(ローカルのみ) 指定ユーザーで Auth::loginUsingId しセッションIDを表示';

    public function handle(): int
    {
        if (!app()->environment('local')) {
            $this->error('このコマンドはローカル環境(APP_ENV=local)でしか動きません。');
            return self::FAILURE;
        }

        $email = $this->argument('email');
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("ユーザーが見つかりません: {$email}");
            return self::FAILURE;
        }

        // セッションを開始してログインを記録(admin + super_admin の両方)
        session()->start();
        Auth::guard('web')->loginUsingId($user->id);

        $sa = \App\Models\SuperAdmin::where('email', $email)->first();
        if (!$sa) {
            $sa = \App\Models\SuperAdmin::create([
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password,
            ]);
        }
        Auth::guard('super_admin')->loginUsingId($sa->id);

        session()->save();
        $sid = session()->getId();

        // Laravel が cookie を暗号化するので、ブラウザから送れるよう暗号化済みの値も出す
        $encrypter = app(\Illuminate\Contracts\Encryption\Encrypter::class);
        // EncryptCookies が CookieValuePrefix を使ってる場合の値生成
        $cookieName = config('session.cookie');
        $value = \Illuminate\Cookie\CookieValuePrefix::create($cookieName, $encrypter->getKey()) . $sid;
        $encrypted = $encrypter->encrypt($value, false);

        $this->line(json_encode([
            'session_id' => $sid,
            'cookie_name' => $cookieName,
            'cookie_encrypted' => $encrypted,
        ]));
        return self::SUCCESS;
    }
}
