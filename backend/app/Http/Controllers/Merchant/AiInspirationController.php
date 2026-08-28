<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AiInspiration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiInspirationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['type' => ['nullable', 'in:all,activity,poster'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:50']]);
        $type = $data['type'] ?? 'all';
        $query = AiInspiration::query()->where('is_online', 1)->when($type !== 'all', fn ($builder) => $builder->where('type', $type));
        $list = (clone $query)->orderBy('sort')->orderByDesc('id')->paginate($data['per_page'] ?? 16);
        $quickPrompts = (clone $query)->inRandomOrder()->limit(2)->get()->map(static fn (AiInspiration $item) => [
            'id' => (int) $item->id, 'type' => $item->type, 'content' => $item->quick_prompt ?: '', 'prompt' => $item->prompt ?: '',
        ])->values();
        return response()->json([
            'quick_prompts' => $quickPrompts,
            'items' => collect($list->items())->map(fn (AiInspiration $item) => $this->payload($item))->values(),
            'per_page' => $list->perPage(), 'current_page' => $list->currentPage(), 'total' => $list->total(),
        ]);
    }

    public function show(int $inspiration): JsonResponse
    {
        $item = AiInspiration::query()->where('is_online', 1)->findOrFail($inspiration);
        return response()->json($this->payload($item));
    }

    private function payload(AiInspiration $item): array
    {
        return [
            'id' => (int) $item->id, 'type' => $item->type, 'title' => $item->title,
            'prompt' => $item->prompt, 'quick_prompt' => $item->quick_prompt, 'activity_id' => $item->activity_id,
            'image_url' => $item->image_url, 'cover_img' => $item->image_url, 'preview_image' => $item->image_url,
            'like_count' => 0, 'used_count' => 0, 'created_at' => optional($item->created_at)->toDateTimeString(),
        ];
    }
}
