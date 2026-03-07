<?php

// Redirect to a URL and stop execution.
function redirect(string $url, int $status = 302): never {
    http_response_code($status);
    header("Location: {$url}");
    exit;
}

// Redirect back to the previous page, with a fallback URL.
function back(string $fallback = '/', int $status = 302): never {
    $url = $_SERVER['HTTP_REFERER'] ?? $fallback;
    redirect($url, $status);
}

// Send a JSON response and stop execution.
function json(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Send an HTTP error response and stop execution.
function abort(int $status, string $message = ''): never {
    http_response_code($status);
    if ($message) echo $message;
    exit;
}
