<?php

$command = $argv[1] ?? null;

// Strip the command name so core files see a clean $argv
$argv = array_values(array_merge([$argv[0]], array_slice($argv, 2)));

$commands = [
    'migrate'  => __DIR__ . '/core/migrate.php',
    'seed'     => __DIR__ . '/core/seed.php',
    'schedule' => __DIR__ . '/core/schedule.php',
    'test'     => __DIR__ . '/core/test.php',
];

if (!$command || !isset($commands[$command])) {
    $list = implode(', ', array_keys($commands));
    die("Unknown command. Available: {$list}\n");
}

require $commands[$command];
