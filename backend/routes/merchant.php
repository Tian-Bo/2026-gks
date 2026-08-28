<?php

use App\Http\Controllers\Merchant\HealthController;
use App\Http\Controllers\UpstreamAiProxyController;
use Illuminate\Support\Facades\Route;

Route::get('health', [HealthController::class, 'show']);

Route::post('merchants', [UpstreamAiProxyController::class, 'merchant']);
Route::post('merchants/login', [UpstreamAiProxyController::class, 'merchant']);
Route::post('merchants/login/by/phone', [UpstreamAiProxyController::class, 'merchant']);

Route::get('shops', [UpstreamAiProxyController::class, 'merchant']);
Route::post('patch/shops/{shop}/current', [UpstreamAiProxyController::class, 'merchant']);
Route::get('items', [UpstreamAiProxyController::class, 'merchant']);
Route::get('activities/{activity}', [UpstreamAiProxyController::class, 'merchant']);
Route::post('patch/activities/{activity}', [UpstreamAiProxyController::class, 'merchant']);
Route::post('patch/activity/release/{activity}', [UpstreamAiProxyController::class, 'merchant']);

Route::any('shop/ai/{upstream_tail}', [UpstreamAiProxyController::class, 'merchant'])
    ->where('upstream_tail', '.*');
