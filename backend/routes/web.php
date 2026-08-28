<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityPreviewController;

Route::get('/', [ActivityPreviewController::class, 'show']);
