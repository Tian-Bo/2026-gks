<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiActivity;
use App\Models\AiMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class AiChatService
{
    public function merchantId(): int
    {
        return (int) (app('request')->attributes->get('merchant_id') ?: env('AI_MERCHANT_ID', 1));
    }

    public function shopId(?int $shopId): int
    {
        return $shopId ?: (int) (app('request')->attributes->get('shop_id') ?: env('AI_SHOP_ID', 1));
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
        $seq = 1;
        $base = function () use (&$seq, $assistant, $conversation): array {
            return ['conversation_id' => $conversation->conversation_id, 'assistant_message_id' => $assistant->message_id, 'seq' => $seq++, 'created_at' => now()->toDateTimeString()];
        };
        $emit('connected', $base());
        $emit('message_start', $base());
        try {
            $reply = $isPoster ? $this->posterReply($conversation) : $this->activityReply($conversation);
        } catch (Throwable $exception) {
            $message = '图片生成失败：' . mb_substr(trim($exception->getMessage()), 0, 300);
            $assistant->update(['status' => 'error', 'content' => $message, 'error_message' => $message, 'completed_at' => now()]);
            $emit('error', array_merge($base(), ['message' => $message]));
            $emit('done', array_merge($base(), ['finish_reason' => 'error']));
            return;
        }
        $text = $reply['text'];
        $components = $reply['components'];
        $emit('thinking_delta', array_merge($base(), ['delta' => $reply['thinking']]));
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
            $meta['poster'] = $reply['poster'] ?? null;
        }
        if (!empty($reply['activity'])) {
            $meta['activity'] = $reply['activity'];
        }
        $assistant->update(['status' => 'completed', 'content' => $text, 'meta' => $meta, 'completed_at' => now()]);
        $conversation->update(['latest_message_at' => now()]);
        if (!empty($reply['activity'])) {
            $emit('activity_generated', array_merge($base(), ['activity' => $reply['activity']]));
        }
        if (!empty($meta['poster'])) {
            $emit('poster_generated', array_merge($base(), ['poster' => $meta['poster']]));
        }
        $emit('message_completed', array_merge($base(), [
            'content' => $text,
            'status' => 'completed',
            'components' => $components,
            'activity' => $meta['activity'] ?? null,
            'poster' => $meta['poster'] ?? null,
        ]));
        $emit('done', array_merge($base(), ['finish_reason' => 'completed']));
    }

    /**
     * Keep the standalone activity conversation compatible with the original
     * merchant workflow. The frontend submits a component_result for each card.
     * That result is the authoritative state transition for the next response.
     *
     * @return array{text: string, thinking: string, components: array<int, array<string, mixed>>, activity?: array<string, mixed>}
     */
    private function activityReply(AiConversation $conversation): array
    {
        $lastUserMessage = $conversation->messages()
            ->where('role', 'user')
            ->orderByDesc('id')
            ->first();
        $result = is_array($lastUserMessage?->component_result) ? $lastUserMessage->component_result : [];
        $stepKey = (string) ($result['step_key'] ?? '');
        $status = (string) ($result['status'] ?? '');
        $meta = $conversation->meta ?? [];
        $draft = is_array($meta['activity_draft'] ?? null) ? $meta['activity_draft'] : [];

        if ($stepKey === 'activity_goal_duration') {
            $draft['goal'] = $status === 'skipped'
                ? ['value' => '拉新获客', 'label' => '拉新获客']
                : $this->namedValue($result['goal'] ?? null, '拉新获客');
            $draft['duration'] = $status === 'skipped'
                ? ['value' => '最近7天', 'label' => '最近 7 天']
                : $this->durationValue($result['duration'] ?? null);
            $draft['next_step'] = 'activity_select_items';
            $this->saveActivityDraft($conversation, $draft);

            $prefix = $status === 'skipped'
                ? '已收到你跳过“活动目标与时间”这一步。我会先按“拉新获客、最近 7 天”继续。'
                : '已收到你的活动偏好：核心目标定为“' . $draft['goal']['label'] . '”，活动周期选择“' . $draft['duration']['label'] . '”。';

            return $this->itemSelectorReply($prefix);
        }

        if ($stepKey === 'activity_select_items') {
            $items = is_array($result['items'] ?? null) ? $result['items'] : [];
            $draft['items'] = $items;
            $draft['item_requirement'] = trim((string) ($result['item_requirement'] ?? ''));
            $draft['next_step'] = 'activity_style_preference';
            $this->saveActivityDraft($conversation, $draft);

            $titles = collect($items)
                ->map(static fn ($item) => is_array($item) ? trim((string) ($item['title'] ?? '')) : '')
                ->filter()
                ->values()
                ->all();
            $prefix = $status === 'skipped'
                ? '已收到你跳过“主推项目”这一步，我会在生成方案时保留默认体验项目。'
                : ($titles === []
                    ? '已收到你的主推项目要求。'
                    : '已收到你选择的主推项目：' . implode('、', $titles) . '。');

            return $this->styleSelectorReply($prefix);
        }

        if ($stepKey === 'activity_style_preference') {
            $draft['style'] = $status === 'skipped'
                ? ['value' => 'general', 'label' => '通用风格']
                : $this->namedValue($result['style'] ?? null, '通用风格');
            $draft['style_requirement'] = trim((string) ($result['style_requirement'] ?? ''));
            $draft['next_step'] = 'activity_deep_confirm';
            $this->saveActivityDraft($conversation, $draft);

            $prefix = $status === 'skipped'
                ? '已收到你跳过“风格偏好”这一步，我会按通用风格继续。'
                : '已收到你的风格偏好：“' . $draft['style']['label'] . '”。';

            return $this->deepConfirmReply($draft, $prefix);
        }

        if ($stepKey === 'activity_deep_confirm') {
            $draft['next_step'] = 'completed';
            $this->saveActivityDraft($conversation, $draft);
            $selection = is_array(data_get($conversation->meta, 'current_selection'))
                ? data_get($conversation->meta, 'current_selection')
                : [];
            $generatedImage = app(RealImageGenerationService::class)->generateActivityCover(
                (string) $conversation->title,
                $draft,
                $selection,
            );
            $coverImage = (string) $generatedImage['url'];
            $activity = $this->generatedActivity($conversation, $draft, $coverImage);

            return [
                'text' => '活动方案已生成，可在右侧预览中查看并继续调整。',
                'thinking' => '已完成活动主视觉生成。',
                'components' => [[
                    'card_id' => $this->businessId('activity_cover'),
                    'type' => 'activity_cover_preview',
                    'status' => 'completed',
                    'title' => '活动主图已生成',
                    'image_url' => $coverImage,
                    'aspect_ratio' => '3:4',
                    'image_provider' => $generatedImage['image_provider'],
                    'provider_model' => $generatedImage['actual_model'],
                ]],
                'activity' => $activity,
            ];
        }

        return match ((string) ($draft['next_step'] ?? '')) {
            'activity_select_items' => $this->itemSelectorReply('活动目标和时间已确认，继续选择主推项目。'),
            'activity_style_preference' => $this->styleSelectorReply('主推项目已确认，继续选择活动风格。'),
            'activity_deep_confirm' => $this->deepConfirmReply($draft, '活动信息已确认。'),
            'completed' => [
                'text' => '活动方案已生成，可继续告诉我想调整的内容。',
                'thinking' => '正在读取已生成的活动方案...',
                'components' => [],
            ],
            default => $this->goalDurationReply(),
        };
    }

    /** @return array{text: string, thinking: string, components: array<int, array<string, mixed>>} */
    private function goalDurationReply(): array
    {
        return [
            'text' => '我已理解你的活动诉求。先确定活动目标和时间，我再为你匹配玩法和活动方案。',
            'thinking' => '正在拆解目标、商品与活动周期...',
            'components' => [[
                'card_id' => $this->businessId('goal'),
                'type' => 'activity_goal_duration_selector',
                'version' => 1,
                'title' => '先确定本次活动目标和时间，我再给你匹配玩法',
                'sub_title' => '这一步只需要 10 秒，选完后我会继续生成活动方案',
                'can_skip' => true,
                'skip_button_text' => '跳过',
                'submit_mode' => 'manual',
                'submit_button_text' => '下一步',
                'step_key' => 'activity_goal_duration',
                'scene' => 'merchant_assistant',
                'sections' => [
                    ['section_key' => 'goal', 'title' => '本次店庆的核心目标是什么？', 'required' => true, 'selection_mode' => 'single', 'options' => [
                        ['value' => '拉新获客', 'label' => '拉新获客'],
                        ['value' => '老客复购', 'label' => '老客复购'],
                        ['value' => '提升客单价', 'label' => '提升客单价'],
                        ['value' => '会员储值', 'label' => '会员储值'],
                    ]],
                    ['section_key' => 'duration', 'title' => '活动计划的起止时间是？或者大概持续几天？', 'selection_mode' => 'single', 'options' => [
                        ['value' => '最近10天', 'label' => '最近 10 天'],
                        ['value' => 'custom_range', 'label' => '自定义时间', 'action' => 'open_date_picker'],
                    ]],
                ],
            ]],
        ];
    }

    /** @return array{text: string, thinking: string, components: array<int, array<string, mixed>>} */
    private function itemSelectorReply(string $prefix): array
    {
        return [
            'text' => $prefix . ' 下一步请选择本次活动的主推项目。',
            'thinking' => '正在匹配商品承接与优惠方式...',
            'components' => [[
                'card_id' => $this->businessId('item'),
                'type' => 'activity_item_selector',
                'version' => 1,
                'step_key' => 'activity_select_items',
                'scene' => 'merchant_assistant',
                'title' => '请选择本次活动的主推项目',
                'sub_title' => '先选项目，我再继续帮你设计优惠方式和活动页面',
                'selector_type' => 'mixed_items',
                'selection_mode' => 'multiple',
                'min_select_count' => 1,
                'max_select_count' => 3,
                'can_skip' => true,
                'skip_button_text' => '跳过',
                'submit_mode' => 'manual',
                'submit_button_text' => '下一步',
            ]],
        ];
    }

    /** @return array{text: string, thinking: string, components: array<int, array<string, mixed>>} */
    private function styleSelectorReply(string $prefix): array
    {
        return [
            'text' => $prefix . ' 接下来请选一下活动氛围的风格偏好。',
            'thinking' => '正在规划活动氛围与页面视觉方向...',
            'components' => [[
                'card_id' => $this->businessId('style'),
                'type' => 'activity_style_selector',
                'version' => 1,
                'step_key' => 'activity_style_preference',
                'scene' => 'merchant_assistant',
                'title' => '活动氛围有什么风格偏好？',
                'sub_title' => '选一个你更喜欢的视觉方向，我会按这个风格继续生成活动文案和页面建议',
                'selection_mode' => 'single',
                'can_skip' => true,
                'skip_button_text' => '跳过',
                'submit_mode' => 'manual',
                'submit_button_text' => '下一步',
                'options' => [
                    ['value' => 'general', 'label' => '通用风格', 'describe' => '由快灵自动匹配'],
                    ['value' => 'trend_3d', 'label' => '3D潮玩', 'describe' => '高对比、强视觉记忆点'],
                    ['value' => 'light_luxury', 'label' => '轻奢质感', 'describe' => '克制、精致、适合高客单'],
                ],
            ]],
        ];
    }

    /** @return array{text: string, thinking: string, components: array<int, array<string, mixed>>} */
    private function deepConfirmReply(array $draft, string $prefix): array
    {
        $goal = (string) data_get($draft, 'goal.label', '拉新获客');
        $duration = (string) data_get($draft, 'duration.label', '最近 10 天');
        $style = (string) data_get($draft, 'style.label', '通用风格');
        $items = collect($draft['items'] ?? [])->map(static fn ($item) => is_array($item) ? (string) ($item['title'] ?? '') : '')->filter()->values()->all();
        $itemLabel = $items === [] ? (trim((string) ($draft['item_requirement'] ?? '')) ?: '默认体验项目') : implode('、', $items);
        $summary = implode("\n", [
            '活动目标：' . $goal,
            '活动周期：' . $duration,
            '主推项目：' . $itemLabel,
            '视觉风格：' . $style,
        ]);

        return [
            'text' => $prefix . ' 我已整理好活动方案，请确认后开始生成。',
            'thinking' => '正在整合活动目标、时间、商品和视觉风格...',
            'components' => [[
                'card_id' => $this->businessId('activity_confirm'),
                'type' => 'activity_deep_confirm',
                'version' => 1,
                'step_key' => 'activity_deep_confirm',
                'scene' => 'merchant_assistant',
                'title' => '确认活动方案',
                'sub_title' => '确认后将开始创建活动并生成主图。',
                'submit_button_text' => '确认并开始生成',
                'thinking' => '已完成方案拆解，将按以下信息生成活动页面。',
                'summary' => $summary,
                'plan' => [
                    'activity_goal' => $goal,
                    'activity_duration' => $duration,
                    'selected_items' => $itemLabel,
                    'style' => $style,
                ],
            ]],
        ];
    }

    /** @return array{text: string, thinking: string, components: array<int, array<string, mixed>>, poster: array<string, mixed>} */
    private function posterReply(AiConversation $conversation): array
    {
        $user = $conversation->messages()->where('role', 'user')->orderByDesc('id')->first();
        $options = is_array($user?->meta['options'] ?? null) ? $user->meta['options'] : [];
        $generated = app(RealImageGenerationService::class)->generatePoster((string) ($user?->content ?? ''), $options);
        $ratio = (string) ($options['aspect_ratio'] ?? '3:4');
        [$width, $height] = $this->posterDimensions($ratio);
        $poster = [
            'url' => $generated['url'],
            'width' => $width,
            'height' => $height,
            'aspect_ratio' => $ratio,
            'style' => (string) ($options['style'] ?? 'general'),
            'style_title' => (string) ($options['style'] ?? '通用风格'),
            'image_model' => (string) ($options['image_model'] ?? $generated['actual_model']),
            'provider_model' => $generated['actual_model'],
            'image_provider' => $generated['image_provider'],
            'prompt' => $generated['prompt'],
            'revised_prompt' => $generated['revised_prompt'],
            'status' => 'created',
        ];

        return [
            'text' => '海报已按你的主题、风格和比例生成完成。',
            'thinking' => '已完成海报主视觉生成与画面整理。',
            'components' => [[
                'card_id' => $this->businessId('poster'),
                'type' => 'poster_image_preview',
                'status' => 'completed',
                'title' => '海报已生成',
                'image_url' => $poster['url'],
                'aspect_ratio' => $ratio,
                'width' => $width,
                'height' => $height,
                'poster' => $poster,
            ]],
            'poster' => $poster,
        ];
    }

    private function saveActivityDraft(AiConversation $conversation, array $draft): void
    {
        $conversation->update(['meta' => array_merge($conversation->meta ?? [], ['activity_draft' => $draft])]);
    }

    /** @return array{value: string, label: string} */
    private function namedValue(mixed $value, string $fallback): array
    {
        if (is_array($value)) {
            $selected = trim((string) ($value['label'] ?? $value['value'] ?? ''));
            if ($selected !== '') {
                return ['value' => (string) ($value['value'] ?? $selected), 'label' => (string) ($value['label'] ?? $selected)];
            }
        }
        return ['value' => $fallback, 'label' => $fallback];
    }

    /** @return array{value: string, label: string, start_time?: string|null, end_time?: string|null} */
    private function durationValue(mixed $value): array
    {
        $duration = $this->namedValue($value, '最近 10 天');
        if (is_array($value)) {
            $duration['start_time'] = isset($value['start_time']) ? (string) $value['start_time'] : null;
            $duration['end_time'] = isset($value['end_time']) ? (string) $value['end_time'] : null;
        }
        return $duration;
    }

    /** @return array<string, mixed> */
    private function generatedActivity(AiConversation $conversation, array $draft, ?string $coverImage = null): array
    {
        $cover = $coverImage ?: AiCatalog::ACTIVITY_IMAGE;
        $activity = AiActivity::query()->firstOrNew([
            'source_conversation_id' => $conversation->conversation_id,
        ]);
        $activity->fill([
            'merchant_id' => $conversation->merchant_id,
            'shop_id' => $conversation->shop_id ?: $this->shopId(null),
            'activity_model_id' => 1,
            'title' => trim((string) $conversation->title) ?: 'AI 生成活动方案',
            'status' => 'draft',
            'cover_img' => $cover,
            'components' => [[
                'type' => 'main_graph',
                'cover_img' => $cover,
                'image_url' => $cover,
            ]],
            'meta' => ['draft' => $draft, 'cover_img' => $cover],
        ]);
        $activity->save();

        return [
            'activity_id' => $activity->id,
            'activity_model_id' => 1,
            'title' => $activity->title,
            'status' => 'draft',
            'cover_img' => $cover,
            'preview_url' => null,
            'draft' => $draft,
        ];
    }

    /** @return array{0: int, 1: int} */
    private function posterDimensions(string $ratio): array
    {
        return match ($ratio) {
            '1:1' => [1024, 1024],
            '1:3' => [672, 2016],
            default => [1024, 1360],
        };
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

    /** @return array{body: string, content_type: string} */
    public function image(string $assistantMessageId, string $url): array
    {
        $message = $this->ownedAssistant($assistantMessageId);
        $meta = $message->meta ?? [];
        $imageUrls = collect(data_get($meta, 'components', []))
            ->pluck('image_url')
            ->filter(static fn ($value) => is_string($value) && $value !== '')
            ->push(data_get($meta, 'poster.url'))
            ->push(data_get($meta, 'activity.cover_img'))
            ->filter(static fn ($value) => is_string($value) && $value !== '')
            ->all();

        abort_unless(in_array($url, $imageUrls, true), 404);

        $response = Http::timeout(30)->connectTimeout(10)->get($url);
        abort_unless($response->successful(), 404);
        $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        abort_unless(str_starts_with($contentType, 'image/'), 404);

        return ['body' => $response->body(), 'content_type' => $contentType];
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
