<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AiMerchant;
use App\Models\AiShop;
use App\Services\MerchantAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $merchantId = (int) $request->attributes->get('merchant_id');
        $merchant = AiMerchant::findOrFail($merchantId);
        $shops = AiShop::query()->where('merchant_id', $merchantId)->orderByDesc('id')->get();
        return response()->json([
            'current_shop_id' => $merchant->last_shop_id,
            'items' => $shops->map(fn (AiShop $shop) => ['id' => $shop->id, 'name' => $shop->name, 'is_default' => $shop->is_default])->values(),
        ]);
    }

    public function setCurrent(Request $request, int $shop, MerchantAuthService $auth): JsonResponse
    {
        $merchantId = (int) $request->attributes->get('merchant_id');
        $merchant = AiMerchant::findOrFail($merchantId);
        $target = AiShop::query()->where('merchant_id', $merchantId)->findOrFail($shop);
        $merchant->update(['last_shop_id' => $target->id]);
        return response()->json($auth->issueToken($merchant->fresh(), $target));
    }
}
