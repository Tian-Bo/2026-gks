<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
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
        if ($request instanceof Request && $request->is('merchant/v1/*')) {
            if ($exception instanceof QueryException) {
                return response()->json(['message' => 'AI 数据库不可用，请检查当前数据库连接配置。'], 503);
            }
            if ($exception instanceof ModelNotFoundException) {
                return response()->json(['message' => '资源不存在'], 404);
            }
        }

        return parent::render($request, $exception);
    }
}
