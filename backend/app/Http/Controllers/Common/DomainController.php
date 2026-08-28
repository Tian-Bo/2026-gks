<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $origin = rtrim($request->getSchemeAndHttpHost(), '/');
        return response()->json([
            'apis' => ['key' => 'apis', 'name' => 'API 接口', 'url' => $origin],
            'preview' => ['key' => 'preview', 'name' => '活动预览', 'url' => $origin],
        ]);
    }
}
