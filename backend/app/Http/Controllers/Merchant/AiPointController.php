<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AiPointAccount;
use App\Models\AiPointLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiPointController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $shopId = $this->selectedShopId($request, $request->query('shop_id'));
        $account = AiPointAccount::query()->where('shop_id', $shopId)->first();
        return response()->json([
            'balance' => (int) ($account?->balance ?? 0),
            'monthly_grant_remaining' => (int) ($account?->monthly_grant_remaining ?? 0),
            'trial' => [
                'activity_create_remaining' => (int) ($account?->trial_activity_remaining ?? 0),
                'poster_generate_remaining' => (int) ($account?->trial_poster_remaining ?? 0),
            ],
        ]);
    }

    public function ledgers(Request $request): JsonResponse
    {
        $data = $request->validate(['shop_id' => ['nullable', 'integer'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $shopId = $this->selectedShopId($request, $data['shop_id'] ?? null);
        $list = AiPointLedger::query()->where('shop_id', $shopId)->orderByDesc('id')->paginate($data['per_page'] ?? 20);
        return response()->json([
            'items' => collect($list->items())->map(static fn (AiPointLedger $item) => [
                'id' => (int) $item->id, 'direction' => $item->direction, 'amount' => (int) $item->amount,
                'balance_after' => (int) $item->balance_after, 'source' => $item->source,
                'billing_item' => $item->billing_item, 'ref_type' => $item->ref_type, 'ref_id' => $item->ref_id,
                'meta' => $item->meta ?? [], 'created_at' => optional($item->created_at)->toDateTimeString(),
            ])->values(),
            'per_page' => $list->perPage(), 'current_page' => $list->currentPage(), 'total' => $list->total(),
        ]);
    }

    private function selectedShopId(Request $request, mixed $requestedShopId): int
    {
        $tokenShopId = (int) $request->attributes->get('shop_id');
        abort_unless($tokenShopId > 0, 401, '请先选择店铺');
        if ($requestedShopId !== null && $requestedShopId !== '' && (int) $requestedShopId !== $tokenShopId) {
            abort(403, '请求店铺与当前令牌不一致');
        }
        return $tokenShopId;
    }
}
