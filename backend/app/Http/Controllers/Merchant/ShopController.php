<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AiMerchant;
use App\Models\AiShop;
use App\Models\LegacyMerchant;
use App\Models\LegacyShop;
use App\Services\MerchantAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $merchantId = (int) $request->attributes->get('merchant_id');
        $merchant = AiMerchant::find($merchantId);
        if ($merchant) {
            $shops = AiShop::query()->where('merchant_id', $merchantId)->orderByDesc('id')->get();
            $currentShopId = (int) $merchant->last_shop_id;
        } else {
            LegacyMerchant::findOrFail($merchantId);
            $shops = LegacyShop::query()->where('merchant_id', $merchantId)->orderByDesc('id')->get();
            $currentShopId = (int) $request->attributes->get('shop_id');
        }

        return response()->json([
            'current_shop_id' => $currentShopId,
            'items' => $shops->map(fn ($shop) => [
                'id' => $shop->id,
                'name' => $shop->name,
                'is_default' => (bool) ($shop->is_default ?? false),
            ])->values(),
        ]);
    }

    public function setCurrent(Request $request, int $shop, MerchantAuthService $auth): JsonResponse
    {
        $merchantId = (int) $request->attributes->get('merchant_id');
        $merchant = AiMerchant::find($merchantId);
        if ($merchant) {
            $target = AiShop::query()->where('merchant_id', $merchantId)->findOrFail($shop);
            $merchant->update(['last_shop_id' => $target->id]);
        } else {
            LegacyMerchant::findOrFail($merchantId);
            $target = LegacyShop::query()->where('merchant_id', $merchantId)->findOrFail($shop);
        }

        return response()->json($auth->issueToken($merchantId, (int) $target->id));
    }
}
