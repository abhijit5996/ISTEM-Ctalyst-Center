<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$publicPath = __DIR__ . '/../public';
$requestedPath = realpath($publicPath . $uri);

if ($uri !== '/' && $requestedPath && str_starts_with($requestedPath, realpath($publicPath)) && is_file($requestedPath)) {
    return false;
}

require __DIR__ . '/../index.php';
