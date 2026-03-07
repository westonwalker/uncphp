<?php

// Get a value from POST or GET input, or all input as an array.
function request(?string $key = null, mixed $default = null): mixed {
    $input = array_merge($_GET, $_POST);
    if ($key === null) return $input;
    return $input[$key] ?? $default;
}

// Get a value from the query string ($_GET) only.
function query(?string $key = null, mixed $default = null): mixed {
    if ($key === null) return $_GET;
    return $_GET[$key] ?? $default;
}

// Get a value from the request body ($_POST) only.
function body(?string $key = null, mixed $default = null): mixed {
    if ($key === null) return $_POST;
    return $_POST[$key] ?? $default;
}

// Get an uploaded file from $_FILES.
function input_file(string $key): array|null {
    return $_FILES[$key] ?? null;
}

// Check if a key exists in POST or GET input.
function has(string $key): bool {
    return isset($_GET[$key]) || isset($_POST[$key]);
}

// Get the current HTTP method.
function method(): string {
    return $_SERVER['REQUEST_METHOD'];
}

// Check if the current request matches the given method.
function is_method(string $method): bool {
    return strcasecmp($_SERVER['REQUEST_METHOD'], $method) === 0;
}

// Check if the request was made via AJAX (XMLHttpRequest).
function is_ajax(): bool {
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

// Get the client's IP address.
function ip(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
}

// Flash current POST input to the session so old() can read it on the next request.
function flash_input(): void {
    $_SESSION['_old_input'] = $_POST;
}

// Get a previously flashed input value (used to repopulate forms after failed validation).
function old(string $key, mixed $default = ''): mixed {
    return $_SESSION['_old_input'][$key] ?? $default;
}
