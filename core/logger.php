<?php

const LOG_LEVELS = [
    'debug'     => 0,
    'info'      => 1,
    'notice'    => 2,
    'warning'   => 3,
    'error'     => 4,
    'critical'  => 5,
    'alert'     => 6,
    'emergency' => 7,
];

function log_write(string $level, string $message): void {
    $min = LOG_LEVELS[strtolower($_ENV['LOG_LEVEL'] ?? 'error')] ?? LOG_LEVELS['error'];

    if (LOG_LEVELS[$level] < $min) {
        return;
    }

    $log_dir = dirname(__DIR__) . '/logs';

    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $line = '[' . date('Y-m-d H:i:s') . '] ' . strtoupper($level) . ': ' . $message . PHP_EOL;

    file_put_contents($log_dir . '/' . date('Y-m-d') . '.log', $line, FILE_APPEND);
}

function log_debug(string $message): void     { log_write('debug',     $message); }
function log_info(string $message): void      { log_write('info',      $message); }
function log_notice(string $message): void    { log_write('notice',    $message); }
function log_warning(string $message): void   { log_write('warning',   $message); }
function log_error(string $message): void     { log_write('error',     $message); }
function log_critical(string $message): void  { log_write('critical',  $message); }
function log_alert(string $message): void     { log_write('alert',     $message); }
function log_emergency(string $message): void { log_write('emergency', $message); }
