<?php

use Illuminate\Support\Facades\Route;

Route::get('/', static fn () => response()->json(['service' => 'kl-ai-laravel', 'status' => 'ok']));
