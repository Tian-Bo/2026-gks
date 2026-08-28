<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AiItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shop_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $merchantId = (int) $request->attributes->get('merchant_id');
        $shopId = (int) $request->attributes->get('shop_id');
        abort_unless($shopId > 0, 401, '请先选择店铺');
        if (isset($data['shop_id']) && (int) $data['shop_id'] !== $shopId) {
            abort(403, '请求店铺与当前令牌不一致');
        }

        $list = AiItem::query()
            ->where('merchant_id', $merchantId)
            ->where('shop_id', $shopId)
            ->when(isset($data['type']) && $data['type'] !== '', fn ($query) => $query->where('type', $data['type']))
            ->when(isset($data['status']), fn ($query) => $query->where('status', $data['status']))
            ->orderByDesc('id')
            ->paginate($data['per_page'] ?? 20);

        return response()->json([
            'items' => collect($list->items())->map(fn (AiItem $item) => $this->payload($item))->values(),
            'per_page' => $list->perPage(),
            'current_page' => $list->currentPage(),
            'total' => $list->total(),
        ]);
    }

    private function payload(AiItem $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'title' => $item->title,
            'name' => $item->title,
            'cover' => $item->cover,
            'base_price' => (float) $item->base_price,
            'stock' => $item->stock,
            'status' => $item->status,
        ];
    }
}
