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

// Connect without a database selected, then create it if needed
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

$dbname = $_ENV['DB_NAME'];
$fresh  = in_array('--fresh', $argv ?? []);

if ($fresh) {
    echo "Dropping database `$dbname` ... ";
    $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
    echo "done.\n";
}

$pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$dbname`");

// Create migrations table if it doesn't exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        filename   VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Get already-applied migrations
$applied = $pdo->query("SELECT filename FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
$applied = array_flip($applied);

// Get all migration files, sorted by name
$files = glob($root . '/migrations/*.sql');
sort($files);

if (empty($files)) {
    echo "No migration files found.\n";
    exit;
}

$ran = 0;

foreach ($files as $file) {
    $filename = basename($file);

    if (isset($applied[$filename])) {
        continue;
    }

    echo "Running: $filename ... ";

    $sql = file_get_contents($file);

    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)")->execute([$filename]);
        try {
            $pdo->commit();
        } catch (PDOException) {
            // DDL caused an implicit commit; everything is already persisted
        }
        echo "done.\n";
        $ran++;
    } catch (PDOException $e) {
        try {
            $pdo->rollBack();
        } catch (PDOException) {
            // nothing to roll back
        }
        echo "FAILED.\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "Stopped. Fix the migration and re-run.\n";
        exit(1);
    }
}

if ($ran === 0) {
    echo "Nothing to migrate. Already up to date.\n";
} else {
    echo "$ran migration(s) applied.\n";
}
