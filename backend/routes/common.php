<?php

use App\Http\Controllers\Common\DomainController;
use App\Http\Controllers\Common\UploadController;
use App\Http\Controllers\Merchant\MerchantAuthController;
use Illuminate\Support\Facades\Route;

Route::get('domain', [DomainController::class, 'show']);
Route::post('sendCode', [MerchantAuthController::class, 'sendSmsCode']);
Route::get('uploads/{path}', [UploadController::class, 'show'])->where('path', '.*');
Route::middleware('merchant.auth')->post('upload', [UploadController::class, 'store']);
