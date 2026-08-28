<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AiChatService
{
    public function merchantId(): int
    {
        return (int) env('AI_MERCHANT_ID', 1);
    }

    public function shopId(?int $shopId): int
    {
        return $shopId ?: (int) env('AI_SHOP_ID', 1);
    }

    public function conversations(?int $shopId, int $perPage)
    {
        return AiConversation::query()
            ->where('merchant_id', $this->merchantId())
            ->when($shopId, static fn ($query) => $query->where('shop_id', $shopId))
            ->orderByDesc('latest_message_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function messages(string $conversationId, int $perPage): array
    {
        $conversation = $this->ownedConversation($conversationId);
        $messages = $conversation->messages()->orderBy('id')->paginate($perPage);
        return [$conversation, $messages];
    }

    public function createTurn(array $payload): array
    {
        $shopId = $this->shopId(isset($payload['shop_id']) ? (int) $payload['shop_id'] : null);
        $isPoster = ($payload['scene'] ?? '') === AiCatalog::POSTER_SCENE
            || data_get($payload, 'component_result.mode') === 'poster';
        $selection = [
            'style' => data_get($payload, 'options.style'),
            'aspect_ratio' => data_get($payload, 'options.aspect_ratio'),
            'activity_model' => data_get($payload, 'options.activity_model'),
            'image_model' => data_get($payload, 'options.image_model'),
            'thinking_mode' => data_get($payload, 'options.thinking_mode', data_get($payload, 'component_result.think_mode')),
        ];

        $conversation = null;
        if (!empty($payload['conversation_id'])) {
            $conversation = AiConversation::query()
                ->where('merchant_id', $this->merchantId())
                ->where('conversation_id', $payload['conversation_id'])
                ->first();
        }

        if (!$conversation) {
            $conversation = AiConversation::create([
                'conversation_id' => $this->businessId('conv'),
                'merchant_id' => $this->merchantId(),
                'shop_id' => $shopId,
                'scene' => $isPoster ? AiCatalog::POSTER_SCENE : 'merchant_assistant',
                'title' => Str::limit((string) $payload['content'], 24, ''),
                'status' => 'active',
                'meta' => ['mode' => $isPoster ? 'poster' : 'activity', 'current_selection' => $selection],
                'latest_message_at' => now(),
            ]);
        } else {
            $conversation->update([
                'meta' => array_merge($conversation->meta ?? [], ['current_selection' => $selection]),
                'latest_message_at' => now(),
            ]);
        }

        $userMessage = AiMessage::create([
            'conversation_record_id' => $conversation->id,
            'message_id' => $payload['user_message_id'] ?? $this->businessId('user'),
            'merchant_id' => $this->merchantId(),
            'shop_id' => $conversation->shop_id,
            'conversation_id' => $conversation->conversation_id,
            'role' => 'user',
            'status' => 'success',
            'content' => (string) $payload['content'],
            'attachments' => $payload['attachments'] ?? [],
            'component_result' => $payload['component_result'] ?? null,
            'meta' => ['options' => $payload['options'] ?? []],
        ]);
        $assistantMessage = AiMessage::create([
            'conversation_record_id' => $conversation->id,
            'message_id' => $this->businessId('assistant'),
            'merchant_id' => $this->merchantId(),
            'shop_id' => $conversation->shop_id,
            'conversation_id' => $conversation->conversation_id,
            'role' => 'assistant',
            'status' => 'pending',
            'content' => '',
            'attachments' => [],
            'meta' => ['mode' => $isPoster ? 'poster' : 'activity', 'components' => []],
            'started_at' => now(),
        ]);

        return [$conversation->fresh(), $userMessage, $assistantMessage];
    }

    public function stream(string $assistantMessageId, callable $emit): void
    {
        $assistant = $this->ownedAssistant($assistantMessageId);
        if ($assistant->status === 'stopped') {
            $emit('done', ['assistant_message_id' => $assistant->message_id, 'finish_reason' => 'stopped']);
            return;
        }
        $conversation = $assistant->conversation;
        $isPoster = $conversation->scene === AiCatalog::POSTER_SCENE || data_get($assistant->meta, 'mode') === 'poster';
        $text = $isPoster
            ? '我已拆解海报主题、目标人群和画面风格，正在生成主视觉方案。'
            : '我已理解你的活动诉求，接下来先确认活动目标和时间，再为你生成活动方案。';
        $components = $isPoster ? [[
            'card_id' => $this->businessId('poster'), 'type' => 'poster_image_preview', 'status' => 'completed',
            'title' => 'AI 海报预览', 'image_url' => AiCatalog::POSTER_IMAGE,
            'poster' => ['url' => AiCatalog::POSTER_IMAGE],
        ]] : [[
            'card_id' => $this->businessId('goal'), 'type' => 'activity_goal_duration_selector',
            'title' => '先确认活动目标和时间', 'step_key' => 'activity_goal_duration',
            'sections' => [
                ['section_key' => 'goal', 'title' => '本次活动的核心目标是什么？', 'options' => [
                    ['value' => '拉新获客', 'label' => '拉新获客'], ['value' => '老客复购', 'label' => '老客复购'], ['value' => '会员储值', 'label' => '会员储值'],
                ]],
                ['section_key' => 'duration', 'title' => '活动计划的起止时间是？', 'options' => [
                    ['value' => '最近10天', 'label' => '最近 10 天'], ['value' => 'custom_range', 'label' => '自定义时间', 'action' => 'open_date_picker'],
                ]],
            ],
        ]];
        $seq = 1;
        $base = function () use (&$seq, $assistant, $conversation): array {
            return ['conversation_id' => $conversation->conversation_id, 'assistant_message_id' => $assistant->message_id, 'seq' => $seq++, 'created_at' => now()->toDateTimeString()];
        };
        $emit('connected', $base());
        $emit('message_start', $base());
        $emit('thinking_delta', array_merge($base(), ['delta' => $isPoster ? '正在构思画面结构和视觉风格...' : '正在拆解目标、商品与活动周期...']));
        foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) as $char) {
            if ($this->isStopped($assistant->message_id)) {
                $emit('done', array_merge($base(), ['finish_reason' => 'stopped']));
                return;
            }
            $emit('message_delta', array_merge($base(), ['delta' => $char]));
            usleep(max(0, (int) env('AI_STREAM_DELAY_MS', 80)) * 1000);
        }
        if ($this->isStopped($assistant->message_id)) {
            $emit('done', array_merge($base(), ['finish_reason' => 'stopped']));
            return;
        }
        foreach ($components as $card) {
            $emit('message_card', array_merge($base(), ['card' => $card]));
        }
        $meta = array_merge($assistant->meta ?? [], ['components' => $components]);
        if ($isPoster) {
            $meta['poster'] = ['url' => AiCatalog::POSTER_IMAGE];
        }
        $assistant->update(['status' => 'completed', 'content' => $text, 'meta' => $meta, 'completed_at' => now()]);
        $conversation->update(['latest_message_at' => now()]);
        $emit('message_completed', array_merge($base(), ['content' => $text, 'status' => 'completed', 'components' => $components, 'poster' => $meta['poster'] ?? null]));
        $emit('done', array_merge($base(), ['finish_reason' => 'completed']));
    }

    public function stop(string $assistantMessageId): AiMessage
    {
        $message = $this->ownedAssistant($assistantMessageId);
        if (in_array($message->status, ['pending', 'streaming'], true)) {
            $message->update(['status' => 'stopped', 'stopped_at' => now()]);
        }
        return $message->fresh();
    }

    public function conversationPayload(AiConversation $conversation): array
    {
        return [
            'conversation_id' => $conversation->conversation_id,
            'merchant_id' => (int) $conversation->merchant_id,
            'shop_id' => $conversation->shop_id === null ? null : (int) $conversation->shop_id,
            'scene' => $conversation->scene,
            'title' => $conversation->title,
            'status' => $conversation->status,
            'preview_image' => $this->previewImage($conversation),
            'preview_image_url' => $this->previewImage($conversation),
            'meta' => $conversation->meta ?? [],
            'current_selection' => data_get($conversation->meta, 'current_selection'),
            'latest_message_at' => optional($conversation->latest_message_at)->toDateTimeString(),
            'created_at' => optional($conversation->created_at)->toDateTimeString(),
            'updated_at' => optional($conversation->updated_at)->toDateTimeString(),
        ];
    }

    public function messagePayload(AiMessage $message): array
    {
        $meta = $message->meta ?? [];
        return [
            'message_id' => $message->message_id,
            'conversation_id' => $message->conversation_id,
            'merchant_id' => (int) $message->merchant_id,
            'shop_id' => $message->shop_id === null ? null : (int) $message->shop_id,
            'role' => $message->role,
            'status' => $message->status,
            'content' => $message->content,
            'attachments' => array_values($message->attachments ?? []),
            'components' => array_values(data_get($meta, 'components', [])),
            'activity' => data_get($meta, 'activity'),
            'poster' => data_get($meta, 'poster'),
            'component_result' => $message->component_result,
            'meta' => $meta,
            'error_code' => $message->error_code,
            'error_message' => $message->error_message,
            'started_at' => optional($message->started_at)->toDateTimeString(),
            'completed_at' => optional($message->completed_at)->toDateTimeString(),
            'stopped_at' => optional($message->stopped_at)->toDateTimeString(),
            'created_at' => optional($message->created_at)->toDateTimeString(),
            'updated_at' => optional($message->updated_at)->toDateTimeString(),
        ];
    }

    private function ownedConversation(string $conversationId): AiConversation
    {
        return AiConversation::query()->where('merchant_id', $this->merchantId())->where('conversation_id', $conversationId)->firstOrFail();
    }

    private function ownedAssistant(string $messageId): AiMessage
    {
        return AiMessage::query()->where('merchant_id', $this->merchantId())->where('message_id', $messageId)->where('role', 'assistant')->firstOrFail();
    }

    private function isStopped(string $messageId): bool
    {
        return AiMessage::query()->where('message_id', $messageId)->value('status') === 'stopped';
    }

    private function previewImage(AiConversation $conversation): ?string
    {
        $message = $conversation->messages()->where('role', 'assistant')->latest('id')->first();
        return data_get($message?->meta, 'poster.url')
            ?? data_get($message?->meta, 'activity.cover_img')
            ?? data_get($conversation->meta, 'poster.url')
            ?? data_get($conversation->meta, 'activity.cover_img');
    }

    private function businessId(string $prefix): string
    {
        return $prefix . '_' . Str::lower((string) Str::uuid());
    }
}
