<?php

namespace App\Http\Middleware;

use App\Models\MerchantAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveMerchantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = trim((string) ($request->input('access_token') ?: $request->bearerToken()));
        if ($token !== '') {
            $record = MerchantAccessToken::query()
                ->where('token_hash', hash('sha256', $token))
                ->where('expires_at', '>', now())
                ->first();

            if ($record) {
                $request->attributes->set('merchant_id', (int) $record->merchant_id);
                $request->attributes->set('shop_id', $record->shop_id === null ? null : (int) $record->shop_id);
            }
        }

        return $next($request);
    }
}
