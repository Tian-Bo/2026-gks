<?php

use App\Http\Controllers\Merchant\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HealthController::class, 'show']);
