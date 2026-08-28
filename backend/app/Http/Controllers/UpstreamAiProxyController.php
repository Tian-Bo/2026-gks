<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class UpstreamAiProxyController extends Controller
{
    public function merchant(Request $request): Response
    {
        return $this->forward($request, $this->requestPath($request, 'merchant/v1/'));
    }

    public function common(Request $request): Response
    {
        return $this->forward($request, $this->requestPath($request, 'common/v1/'));
    }

    private function requestPath(Request $request, string $prefix): string
    {
        $path = ltrim($request->path(), '/');
        abort_unless(str_starts_with($path, $prefix), 404);

        return $path;
    }

    private function forward(Request $request, string $path): Response
    {
        $url = $this->url($path, $request->query());
        $headers = ['Accept' => $request->header('Accept', 'application/json')];
        if ($request->header('Content-Type')) {
            $headers['Content-Type'] = $request->header('Content-Type');
        }
        if ($request->bearerToken()) {
            $headers['Authorization'] = 'Bearer ' . $request->bearerToken();
        }
        if (str_ends_with($path, '/stream')) {
            return $this->stream($url, $headers);
        }

        $client = Http::withHeaders($headers)
            ->timeout((int) config('services.ai_upstream.timeout'));
        $options = [];
        if ($request->allFiles() !== []) {
            $options['multipart'] = $this->multipart($request);
            unset($headers['Content-Type']);
            $client = Http::withHeaders($headers)
                ->timeout((int) config('services.ai_upstream.timeout'));
        } elseif ($request->getContent() !== '') {
            $options['body'] = $request->getContent();
        }

        $upstream = $client->send($request->method(), $url, $options);
        $contentType = $upstream->header('Content-Type') ?: 'application/json';

        return response($upstream->body(), $upstream->status(), ['Content-Type' => $contentType]);
    }

    private function stream(string $url, array $headers): Response
    {
        return response()->stream(function () use ($url, $headers): void {
            @ignore_user_abort(true);
            @set_time_limit((int) config('services.ai_upstream.timeout') + 30);
            $upstreamStatus = 0;
            $errorBody = '';
            $curl = curl_init($url);
            if ($curl === false) {
                $this->emitStreamError('无法建立 AI 流式连接');
                return;
            }

            curl_setopt_array($curl, [
                CURLOPT_HTTPGET => true,
                CURLOPT_HTTPHEADER => collect($headers)->map(fn ($value, $name) => $name . ': ' . $value)->all(),
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_TIMEOUT => (int) config('services.ai_upstream.timeout'),
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_HEADERFUNCTION => static function ($_curl, string $header) use (&$upstreamStatus): int {
                    if (preg_match('/^HTTP\\/\\S+\\s+(\\d{3})/i', $header, $matches)) {
                        $upstreamStatus = (int) $matches[1];
                    }
                    return strlen($header);
                },
                CURLOPT_WRITEFUNCTION => static function ($_curl, string $chunk) use (&$upstreamStatus, &$errorBody): int {
                    if ($upstreamStatus >= 400) {
                        $errorBody .= $chunk;
                        return strlen($chunk);
                    }
                    echo $chunk;
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    flush();
                    return strlen($chunk);
                },
            ]);
            $ok = curl_exec($curl);
            if ($ok === false) {
                $this->emitStreamError('AI 流式服务连接失败，请稍后重试');
            } elseif ($upstreamStatus >= 400) {
                $payload = json_decode($errorBody, true);
                $message = is_array($payload) ? trim((string) ($payload['message'] ?? '')) : '';
                $this->emitStreamError($message !== '' ? $message : 'AI 流式服务请求失败');
            }
            curl_close($curl);
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function emitStreamError(string $message): void
    {
        echo 'event: error' . "\n";
        echo 'data: ' . json_encode(['message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
        flush();
    }

    private function url(string $path, array $query): string
    {
        $baseUrl = rtrim((string) config('services.ai_upstream.base_url'), '/');
        abort_if($baseUrl === '', 503, '未配置线上 AI 服务地址');
        $url = $baseUrl . '/' . ltrim($path, '/');
        return $query === [] ? $url : $url . '?' . http_build_query($query);
    }

    /** @return array<int, array{name: string, contents: mixed, filename?: string}> */
    private function multipart(Request $request): array
    {
        $parts = [];
        foreach ($request->except(array_keys($request->allFiles())) as $name => $value) {
            if (is_scalar($value)) {
                $parts[] = ['name' => (string) $name, 'contents' => (string) $value];
            }
        }
        foreach ($request->allFiles() as $name => $file) {
            if ($file && $file->isValid()) {
                $parts[] = [
                    'name' => (string) $name,
                    'contents' => fopen($file->getRealPath(), 'r'),
                    'filename' => $file->getClientOriginalName(),
                ];
            }
        }

        return $parts;
    }
}
