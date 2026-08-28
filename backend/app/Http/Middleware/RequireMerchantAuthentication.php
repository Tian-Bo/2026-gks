<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMerchantAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!(int) $request->attributes->get('merchant_id')) {
            abort(401, '请先登录商家账号');
        }

        return $next($request);
    }
}
