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

$test_db = $_ENV['DB_TEST_NAME'] ?? null;

if (!$test_db) {
    die("DB_TEST_NAME is not set in .env\n");
}

// Connect without a database selected
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};charset=utf8mb4",
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

// Drop and recreate the test database
echo "Setting up `{$test_db}` ...\n\n";
$pdo->exec("DROP DATABASE IF EXISTS `{$test_db}`");
$pdo->exec("CREATE DATABASE `{$test_db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$test_db}`");

// Run all migrations
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        filename   VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$migration_files = glob($root . '/migrations/*.sql');
sort($migration_files);

foreach ($migration_files as $file) {
    $sql = file_get_contents($file);
    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)")->execute([basename($file)]);
        try { $pdo->commit(); } catch (PDOException) {}
    } catch (PDOException $e) {
        try { $pdo->rollBack(); } catch (PDOException) {}
        die("Migration failed (" . basename($file) . "): " . $e->getMessage() . "\n");
    }
}

// Make $pdo available to app files and tests
require_once $root . '/actions.php';
require_once $root . '/queries.php';
require_once $root . '/mutations.php';

// --- Test registry ---

$tests            = [];
$current_test_file = null;

function test(string $name, callable $fn): void {
    global $tests, $current_test_file;
    $tests[] = ['name' => $name, 'fn' => $fn, 'file' => $current_test_file];
}

// --- Assertion helpers ---

function assert_true(bool $condition, string $message = 'Expected true'): void {
    if (!$condition) throw new Exception($message);
}

function assert_false(bool $condition, string $message = 'Expected false'): void {
    if ($condition) throw new Exception($message);
}

function assert_equals(mixed $expected, mixed $actual, string $message = ''): void {
    if ($expected !== $actual) {
        $label = $message ? "{$message}: " : '';
        throw new Exception("{$label}expected " . json_encode($expected) . ", got " . json_encode($actual));
    }
}

function assert_not_null(mixed $value, string $message = 'Expected a non-null value'): void {
    if ($value === null) throw new Exception($message);
}

function assert_null(mixed $value, string $message = 'Expected null'): void {
    if ($value !== null) throw new Exception("{$message}, got " . json_encode($value));
}

function assert_count(int $expected, array $array, string $message = ''): void {
    $actual = count($array);
    if ($expected !== $actual) {
        $label = $message ? "{$message}: " : '';
        throw new Exception("{$label}expected count {$expected}, got {$actual}");
    }
}

// --- Discover and load test files ---

$test_files = glob($root . '/tests/*_test.php');
sort($test_files);

if (empty($test_files)) {
    die("No test files found in tests/\n");
}

foreach ($test_files as $file) {
    $current_test_file = $file;
    include $file;
}

// --- Run tests ---

$passed       = 0;
$failed       = 0;
$active_file  = null;

foreach ($tests as $t) {
    if ($t['file'] !== $active_file) {
        $active_file = $t['file'];
        echo basename($active_file) . "\n";
    }

    try {
        ($t['fn'])($pdo);
        echo "  \033[32m✓\033[0m {$t['name']}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  \033[31m✗\033[0m {$t['name']}: {$e->getMessage()}\n";
        $failed++;
    }
}

// --- Summary ---

$total = $passed + $failed;
$color = $failed > 0 ? "\033[31m" : "\033[32m";
echo "\n{$color}{$total} tests, {$passed} passed, {$failed} failed\033[0m\n";

// --- Teardown ---

echo "\nDropping `{$test_db}` ...\n";
$pdo->exec("DROP DATABASE IF EXISTS `{$test_db}`");

if ($failed > 0) {
    exit(1);
}
