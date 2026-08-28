<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    public function register(): void
    {
        $this->reportable(function (Throwable $exception) {
        });
    }

    public function render($request, Throwable $exception): Response
    {
        if ($request instanceof Request && ($request->is('merchant/v1/*') || $request->is('common/v1/*'))) {
            if ($exception instanceof QueryException) {
                return response()->json(['message' => 'AI 数据库不可用，请检查当前数据库连接配置。'], 503);
            }
            if ($exception instanceof ModelNotFoundException) {
                return response()->json(['message' => '资源不存在'], 404);
            }
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ], 422);
            }
            if ($exception instanceof HttpExceptionInterface) {
                $message = trim($exception->getMessage()) ?: Response::$statusTexts[$exception->getStatusCode()] ?? '请求失败';
                return response()->json(['message' => $message], $exception->getStatusCode());
            }
        }

        return parent::render($request, $exception);
    }
}
