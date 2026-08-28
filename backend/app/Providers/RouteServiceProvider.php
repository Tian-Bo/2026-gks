<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        $this->routes(function () {
            // Gateway requests are authenticated by the upstream service. Do not
            // query local token tables before forwarding a request.
            Route::prefix('merchant/v1')->middleware('api')->group(base_path('routes/merchant.php'));
            Route::prefix('common/v1')->middleware('api')->group(base_path('routes/common.php'));
            // The gateway landing endpoint is a health response, not a session
            // based page. Avoid requiring cookies or APP_KEY at boot time.
            Route::group([], base_path('routes/web.php'));
        });
    }
}
