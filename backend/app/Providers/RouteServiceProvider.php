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
            Route::prefix('merchant/v1')->middleware(['api', 'merchant.context'])->group(base_path('routes/merchant.php'));
            Route::prefix('common/v1')->middleware(['api', 'merchant.context'])->group(base_path('routes/common.php'));
            Route::middleware('web')->group(base_path('routes/web.php'));
        });
    }
}
