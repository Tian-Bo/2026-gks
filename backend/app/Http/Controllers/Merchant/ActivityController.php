<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AiActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function show(Request $request, int $activity): JsonResponse
    {
        return response()->json($this->payload($this->owned($request, $activity)));
    }

    public function update(Request $request, int $activity): JsonResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'cover_img' => ['nullable', 'string', 'max:2048'],
            'background_color' => ['nullable', 'string', 'max:32'],
            'components' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ]);
        $record = $this->owned($request, $activity);
        $record->fill($data);
        $record->save();
        return response()->json($this->payload($record->fresh()));
    }

    public function release(Request $request, int $activity): JsonResponse
    {
        $record = $this->owned($request, $activity);
        $record->update(['status' => 'published', 'released_at' => now()]);
        return response()->json($this->payload($record->fresh()));
    }

    private function owned(Request $request, int $activity): AiActivity
    {
        return AiActivity::query()
            ->where('merchant_id', (int) $request->attributes->get('merchant_id'))
            ->where('shop_id', (int) $request->attributes->get('shop_id'))
            ->where('id', $activity)
            ->firstOrFail();
    }

    private function payload(AiActivity $activity): array
    {
        $components = $activity->components ?? [];
        return [
            'id' => $activity->id,
            'activity_id' => $activity->id,
            'activity_model_id' => $activity->activity_model_id,
            'title' => $activity->title,
            'status' => $activity->status,
            'cover_img' => $activity->cover_img,
            'background_color' => $activity->background_color,
            'activity_components' => $components,
            'activity_component' => $components,
            'meta' => $activity->meta ?? [],
            'released_at' => optional($activity->released_at)->toDateTimeString(),
            'updated_at' => optional($activity->updated_at)->toDateTimeString(),
        ];
    }
}
