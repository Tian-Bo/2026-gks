<?php

// Laravel 8 does not ship the development router resource used by newer
// framework versions. Let PHP serve static assets and route everything else
// through Laravel's public entry point.
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

require __DIR__ . '/index.php';
