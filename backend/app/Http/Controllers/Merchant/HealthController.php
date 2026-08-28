<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function show(): JsonResponse
    {
        try {
            DB::select('select 1');
        } catch (QueryException $exception) {
            return response()->json([
                'status' => 'unavailable',
                'service' => 'kl-ai-laravel',
                'database' => 'mysql',
            ], 503);
        }

        return response()->json(['status' => 'ok', 'service' => 'kl-ai-laravel', 'database' => 'mysql']);
    }
}
