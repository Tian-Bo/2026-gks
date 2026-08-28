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
        $shopId = (int) ($request->query('shop_id') ?: env('AI_SHOP_ID', 1));
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
        $shopId = (int) ($data['shop_id'] ?? env('AI_SHOP_ID', 1));
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
}
