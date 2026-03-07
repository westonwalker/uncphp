<?php

session_start();

// Load .env
foreach (file(__DIR__ . '/.env') as $line) {
    $line = trim($line);
    if ($line && !str_starts_with($line, '#')) {
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

require_once 'core/errors.php';
require_once 'core/database.php';
require_once 'core/logger.php';
require_once 'core/auth.php';
require_once 'core/request.php';
require_once 'core/response.php';
require_once 'core/routing.php';
require_once 'middleware.php';
require_once 'actions.php';
require_once 'queries.php';
require_once 'mutations.php';
require_once 'routes.php';

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

dispatch($method, $uri);
