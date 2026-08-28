<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class RealImageGenerationService
{
    /**
     * Generate an image through the same provider priority as the original
     * system. Providers return a public URL or Base64 data that the frontend
     * can render without falling back to a placeholder image.
     *
     * @return array<string, mixed>
     */
    public function generatePoster(string $content, array $options = []): array
    {
        $style = trim((string) ($options['style'] ?? '通用风格')) ?: '通用风格';
        $ratio = trim((string) ($options['aspect_ratio'] ?? '3:4')) ?: '3:4';
        $prompt = implode("\n", [
            trim($content) ?: '门店活动宣传海报',
            '生成一张可直接投放的中文门店营销海报。',
            '画面比例：' . $ratio . '。',
            '视觉风格：' . $style . '。',
            '画面有清晰的主视觉、活动主题、项目卖点与行动引导，文字简洁且排版清晰。',
        ]);

        return $this->generate($prompt, $ratio, $this->requestedModel($options));
    }

    /** @return array<string, mixed> */
    public function generateActivityCover(string $title, array $draft, array $options = []): array
    {
        $goal = trim((string) data_get($draft, 'goal.label', '拉新获客'));
        $duration = trim((string) data_get($draft, 'duration.label', '近期活动'));
        $style = trim((string) data_get($draft, 'style.label', data_get($options, 'style', '通用风格')));
        $items = collect($draft['items'] ?? [])
            ->map(static fn ($item) => is_array($item) ? trim((string) ($item['title'] ?? '')) : '')
            ->filter()
            ->values()
            ->all();
        $itemLabel = $items === [] ? '门店主推项目' : implode('、', $items);
        $prompt = implode("\n", [
            trim($title) ?: '门店营销活动',
            '生成活动主图，适合门店营销活动落地页首屏使用。',
            '活动目标：' . $goal . '；活动周期：' . $duration . '；主推项目：' . $itemLabel . '。',
            '视觉风格：' . ($style ?: '通用风格') . '。',
            '竖版 3:4，画面聚焦活动主题和商品卖点，避免乱码和多余文字。',
        ]);

        return $this->generate($prompt, '3:4', $this->requestedModel($options));
    }

    /** @return array<string, mixed> */
    private function generate(string $prompt, string $ratio, ?string $requestedModel): array
    {
        $errors = [];
        foreach ($this->providers() as $provider) {
            try {
                $model = $this->resolveModel($provider, $requestedModel);
                $response = $this->request($provider, $model, $prompt, $ratio);
                if (!$response->successful()) {
                    $errors[] = $this->responseError($provider, $response);
                    continue;
                }

                $item = $response->json('data.0');
                if (!is_array($item)) {
                    $errors[] = $this->providerLabel($provider) . ' 未返回图片数据';
                    continue;
                }

                $url = $this->imageUrl($item);
                if ($url === '') {
                    $errors[] = $this->providerLabel($provider) . ' 未返回可用图片地址';
                    continue;
                }

                return [
                    'url' => $url,
                    'image_provider' => $provider,
                    'actual_model' => $model,
                    'prompt' => $prompt,
                    'size' => $this->sizeFor($ratio),
                    'revised_prompt' => $item['revised_prompt'] ?? null,
                ];
            } catch (Throwable $exception) {
                $errors[] = $this->providerLabel($provider) . '：' . $this->shortError($exception->getMessage());
            }
        }

        throw new RuntimeException('图片生成失败，' . implode('；', $errors));
    }

    /** @return array<int, string> */
    private function providers(): array
    {
        $providers = [];
        // GPT Image 2 is the selected model. Keep the OpenAI-compatible
        // providers ahead of Seedream, which is retained only as a fallback.
        foreach (['xhhai', 'sub2api', 'seedream'] as $provider) {
            if (trim((string) config('services.' . $provider . '.api_key')) !== '') {
                $providers[] = $provider;
            }
        }
        if ($providers === []) {
            throw new RuntimeException('未配置图片生成 API Key。');
        }

        return $providers;
    }

    private function request(string $provider, string $model, string $prompt, string $ratio): Response
    {
        $timeout = min(120, max(60, (int) env('AI_CHAT_POSTER_GENERATION_TIMEOUT', 90)));
        $client = Http::acceptJson()
            ->withToken((string) config('services.' . $provider . '.api_key'))
            ->timeout($timeout)
            ->connectTimeout(15);

        if ($provider === 'seedream') {
            return $client->post(rtrim((string) config('services.seedream.base_url'), '/') . '/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'response_format' => 'url',
                // Seedream no longer accepts the legacy 1K size value. The
                // requested aspect ratio remains part of the prompt.
                'size' => '2k',
                'stream' => false,
                'watermark' => (bool) config('services.seedream.watermark', false),
            ]);
        }

        return $client
            ->withOptions(['verify' => (bool) config('services.' . $provider . '.verify_ssl', true)])
            ->post(rtrim((string) config('services.' . $provider . '.base_url'), '/') . '/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'size' => $this->sizeFor($ratio),
                'n' => 1,
                'quality' => 'high',
            ]);
    }

    private function requestedModel(array $options): ?string
    {
        $model = trim((string) ($options['image_model'] ?? ''));
        if ($model === 'auto') {
            return null;
        }

        // Existing conversations created before the rename used kl-image.
        return $model === 'kl-image' || $model === '' ? 'gpt-image-2' : $model;
    }

    private function resolveModel(string $provider, ?string $requestedModel): string
    {
        if ($provider === 'seedream') {
            return trim((string) config('services.seedream.default_model')) ?: 'doubao-seedream-5-0-260128';
        }

        return $requestedModel ?: (trim((string) config('services.' . $provider . '.image_model')) ?: 'gpt-image-2');
    }

    private function sizeFor(string $ratio): string
    {
        return match ($ratio) {
            '1:1' => '1024x1024',
            '1:3' => '672x2016',
            default => '1024x1360',
        };
    }

    private function imageUrl(array $item): string
    {
        $url = trim((string) ($item['url'] ?? ''));
        if ($url !== '') {
            return $url;
        }

        $base64 = trim((string) ($item['b64_json'] ?? ''));
        return $base64 !== '' ? 'data:image/png;base64,' . $base64 : '';
    }

    private function providerLabel(string $provider): string
    {
        return match ($provider) {
            'xhhai' => 'XHHAI',
            'sub2api' => 'SUB2API',
            default => 'Seedream',
        };
    }

    private function shortError(string $message): string
    {
        return mb_substr(trim($message), 0, 200) ?: '请求失败';
    }

    private function responseError(string $provider, Response $response): string
    {
        $message = trim((string) ($response->json('error.message') ?? $response->json('message') ?? ''));
        $suffix = $message !== '' ? '：' . $this->shortError($message) : '';
        return $this->providerLabel($provider) . ' HTTP ' . $response->status() . $suffix;
    }
}
