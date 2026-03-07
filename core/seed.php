<?php

$root = dirname(__DIR__);

// Load .env
foreach (file($root . '/.env') as $line) {
    $line = trim($line);
    if ($line && !str_starts_with($line, '#')) {
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Connect
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

// Parse args
$args   = array_slice($argv, 1);
$fresh  = in_array('--fresh', $args);
$target = null;

foreach ($args as $arg) {
    if (!str_starts_with($arg, '--')) {
        $target = $arg;
        break;
    }
}

// Resolve seeder files
if ($target) {
    $path = $root . "/seeders/{$target}.php";
    if (!file_exists($path)) {
        die("Seeder not found: {$target}\n");
    }
    $files = [$path];
} else {
    $files = glob($root . '/seeders/*.php');
    sort($files);
}

if (empty($files)) {
    die("No seeders found.\n");
}

// Run
foreach ($files as $file) {
    $name = basename($file, '.php');
    echo "Seeding: $name ... ";
    try {
        include $file;
        echo "done.\n";
    } catch (Throwable $e) {
        echo "FAILED.\n";
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
