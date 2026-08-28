<?php

use App\Http\Controllers\Merchant\AiChatController;
use App\Http\Controllers\Merchant\AiInspirationController;
use App\Http\Controllers\Merchant\AiPointController;
use App\Http\Controllers\Merchant\ActivityController;
use App\Http\Controllers\Merchant\ItemController;
use App\Http\Controllers\Merchant\MerchantAuthController;
use App\Http\Controllers\Merchant\ShopController;
use App\Http\Controllers\Merchant\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('health', [HealthController::class, 'show']);

Route::post('merchants', [MerchantAuthController::class, 'register']);
Route::post('merchants/login', [MerchantAuthController::class, 'login']);
Route::post('merchants/login/by/phone', [MerchantAuthController::class, 'loginByCode']);

Route::get('shop/ai/config', [AiChatController::class, 'config']);
Route::get('shop/ai/prompt-tips', [AiChatController::class, 'promptTips']);
Route::get('shop/ai/inspirations', [AiInspirationController::class, 'index']);
Route::get('shop/ai/inspirations/{inspiration}', [AiInspirationController::class, 'show']);

Route::middleware('merchant.auth')->group(function () {
    Route::get('shops', [ShopController::class, 'index']);
    Route::post('patch/shops/{shop}/current', [ShopController::class, 'setCurrent']);
    Route::get('items', [ItemController::class, 'index']);
    Route::get('activities/{activity}', [ActivityController::class, 'show']);
    Route::post('patch/activities/{activity}', [ActivityController::class, 'update']);
    Route::post('patch/activity/release/{activity}', [ActivityController::class, 'release']);

    Route::prefix('shop/ai')->group(function () {
    Route::get('conversations', [AiChatController::class, 'conversationsIndex']);
    Route::get('conversations/{conversationId}/messages', [AiChatController::class, 'messagesIndex']);
    Route::get('messages/{assistantMessageId}/image', [AiChatController::class, 'imageShow']);
    Route::post('messages', [AiChatController::class, 'messageStore']);
    Route::get('messages/{assistantMessageId}/stream', [AiChatController::class, 'messageStream']);
    Route::post('messages/{assistantMessageId}/stop', [AiChatController::class, 'messageStop']);
    Route::get('points', [AiPointController::class, 'show']);
    Route::get('points/ledgers', [AiPointController::class, 'ledgers']);
    });
});
