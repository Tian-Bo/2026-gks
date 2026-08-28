<?php

// The PHP built-in server is launched as a background service for local use.
// Set its working directory explicitly before Laravel's development router runs.
chdir(__DIR__);

require __DIR__ . '/../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
