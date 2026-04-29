<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            // super-admin配下のルートはsuper-adminログインへ
            if ($request->is('super-admin/*')) {
                return url('/super-admin/login');
            }
            return route('login');
        }
    }
}
