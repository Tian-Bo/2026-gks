<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\AiCatalog;
use App\Services\AiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    public function __construct(private AiChatService $chat)
    {
    }

    public function config(): JsonResponse
    {
        return response()->json(AiCatalog::pageConfig());
    }

    public function promptTips(Request $request): JsonResponse
    {
        $type = trim((string) $request->query('type', ''));
        if ($type !== '' && !in_array($type, ['activity', 'poster'], true)) {
            return response()->json(['message' => 'type 仅支持 activity 或 poster'], 422);
        }
        return response()->json(array_filter([
            'type' => $type ?: null,
            'items' => AiCatalog::promptTips($type ?: null),
        ], static fn ($value) => $value !== null));
    }

    public function conversationsIndex(Request $request): JsonResponse
    {
        $data = $request->validate(['shop_id' => ['nullable', 'integer'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $list = $this->chat->conversations($data['shop_id'] ?? null, $data['per_page'] ?? 15);
        return response()->json([
            'items' => collect($list->items())->map(fn ($item) => $this->chat->conversationPayload($item))->values(),
            'per_page' => $list->perPage(), 'current_page' => $list->currentPage(), 'total' => $list->total(),
        ]);
    }

    public function messagesIndex(Request $request, string $conversationId): JsonResponse
    {
        $data = $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        [$conversation, $list] = $this->chat->messages($conversationId, $data['per_page'] ?? 20);
        $conversationPayload = $this->chat->conversationPayload($conversation);
        $conversationPayload['current_selection'] = $conversationPayload['current_selection'] ?? [
            'style' => null, 'aspect_ratio' => null, 'activity_model' => null, 'image_model' => null, 'thinking_mode' => null,
        ];
        return response()->json([
            'conversation' => $conversationPayload,
            'items' => collect($list->items())->map(fn ($item) => $this->chat->messagePayload($item))->values(),
            'per_page' => $list->perPage(), 'current_page' => $list->currentPage(), 'total' => $list->total(),
        ]);
    }

    public function messageStore(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'conversation_id' => ['nullable', 'string', 'max:64'],
            'user_message_id' => ['nullable', 'string', 'max:64'],
            'shop_id' => ['nullable', 'integer'],
            'scene' => ['nullable', 'string', 'max:64'],
            'content' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            'component_result' => ['nullable', 'array'],
            'options' => ['nullable', 'array'],
        ]);
        [$conversation, $userMessage, $assistantMessage] = $this->chat->createTurn($payload);
        return response()->json([
            'conversation' => $this->chat->conversationPayload($conversation),
            'user_message' => $this->chat->messagePayload($userMessage),
            'assistant_message' => $this->chat->messagePayload($assistantMessage),
            'stream_url' => '/merchant/v1/shop/ai/messages/' . $assistantMessage->message_id . '/stream',
            'assistant_status' => $assistantMessage->status,
        ]);
    }

    public function imageShow(Request $request, string $assistantMessageId)
    {
        $data = $request->validate(['url' => ['required', 'url', 'max:4096']]);
        $image = $this->chat->image($assistantMessageId, $data['url']);

        return response($image['body'], 200, [
            'Content-Type' => $image['content_type'],
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function messageStream(string $assistantMessageId): StreamedResponse
    {
        return response()->stream(function () use ($assistantMessageId) {
            @ignore_user_abort(true);
            @set_time_limit(300);
            $this->chat->stream($assistantMessageId, function (string $event, array $payload): void {
                echo 'event: ' . $event . "\n";
                echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            });
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function messageStop(string $assistantMessageId): JsonResponse
    {
        return response()->json(['message' => $this->chat->messagePayload($this->chat->stop($assistantMessageId))]);
    }
}
