<?php

use App\Http\Controllers\Merchant\AiChatController;
use App\Http\Controllers\Merchant\AiInspirationController;
use App\Http\Controllers\Merchant\AiPointController;
use App\Http\Controllers\Merchant\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('health', [HealthController::class, 'show']);

Route::prefix('shop/ai')->group(function () {
    Route::get('config', [AiChatController::class, 'config']);
    Route::get('prompt-tips', [AiChatController::class, 'promptTips']);
    Route::get('conversations', [AiChatController::class, 'conversationsIndex']);
    Route::get('conversations/{conversationId}/messages', [AiChatController::class, 'messagesIndex']);
    Route::post('messages', [AiChatController::class, 'messageStore']);
    Route::get('messages/{assistantMessageId}/stream', [AiChatController::class, 'messageStream']);
    Route::post('messages/{assistantMessageId}/stop', [AiChatController::class, 'messageStop']);
    Route::get('points', [AiPointController::class, 'show']);
    Route::get('points/ledgers', [AiPointController::class, 'ledgers']);
    Route::get('inspirations', [AiInspirationController::class, 'index']);
    Route::get('inspirations/{inspiration}', [AiInspirationController::class, 'show']);
});
