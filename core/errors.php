<?php

// Convert PHP errors to exceptions so they're caught by the exception handler.
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Handle all uncaught exceptions.
set_exception_handler(function (Throwable $e): void {
    handle_error(get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
});

// Catch fatal errors (parse errors, out of memory, etc.) that bypass the above.
register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        handle_error('Fatal Error', $error['message'], $error['file'], $error['line'], '');
    }
});

function handle_error(string $type, string $message, string $file, int $line, string $trace): void {
    if (function_exists('log_error')) {
        log_error("{$type}: {$message} in {$file}:{$line}");
    }

    http_response_code(500);

    if (($_ENV['APP_ENV'] ?? 'production') === 'local') {
        $type    = htmlspecialchars($type);
        $message = htmlspecialchars($message);
        $file    = htmlspecialchars($file);
        $trace   = htmlspecialchars($trace);

        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Error: {$type}</title>
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body { background: #0f0f0f; color: #e0e0e0; font-family: monospace; padding: 2rem; }
                h1 { color: #e05252; font-size: 1.25rem; margin-bottom: .5rem; }
                .message { font-size: 1rem; color: #fff; margin-bottom: 1.5rem; }
                .meta { font-size: .85rem; color: #888; margin-bottom: 1.5rem; }
                .meta span { color: #5b9bd5; }
                h2 { font-size: .8rem; text-transform: uppercase; letter-spacing: .1em; color: #666; margin-bottom: .75rem; }
                pre { background: #1a1a1a; border: 1px solid #2a2a2a; padding: 1.25rem; overflow-x: auto;
                      font-size: .8rem; line-height: 1.6; color: #ccc; border-radius: 4px; }
            </style>
        </head>
        <body>
            <h1>{$type}</h1>
            <p class="message">{$message}</p>
            <p class="meta">in <span>{$file}</span> on line <span>{$line}</span></p>
            <h2>Stack Trace</h2>
            <pre>{$trace}</pre>
        </body>
        </html>
        HTML;
    } else {
        $view = dirname(__DIR__) . '/views/errors/500.php';
        if (file_exists($view)) {
            include $view;
        } else {
            echo '<h1>Something went wrong. Please try again later.</h1>';
        }
    }

    exit;
}
