<?php

use App\Http\Controllers\UpstreamAiProxyController;
use Illuminate\Support\Facades\Route;

Route::get('domain', [UpstreamAiProxyController::class, 'common']);
Route::post('sendCode', [UpstreamAiProxyController::class, 'common']);
Route::post('upload', [UpstreamAiProxyController::class, 'common']);
Route::get('uploads/{upstream_tail}', [UpstreamAiProxyController::class, 'common'])
    ->where('upstream_tail', '.*');
