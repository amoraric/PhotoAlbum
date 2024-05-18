<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MiddlewareServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app['router']->aliasMiddleware('2fa', \App\Http\Middleware\TwoFactorAuthentication::class);
    }

    public function boot()
    {
        //
    }
}
