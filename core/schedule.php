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

require_once $root . '/core/logger.php';

// Create schedule_runs table if it doesn't exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS schedule_runs (
        name     VARCHAR(255) NOT NULL PRIMARY KEY,
        last_run TIMESTAMP NULL DEFAULT NULL
    )
");

// Returns true if a single cron field value matches the current time value.
// Supports: * | */n | n | n,m | n-m | n-m/step
function cron_field_matches(string $field, int $current): bool {
    if ($field === '*') {
        return true;
    }

    foreach (explode(',', $field) as $part) {
        if (str_contains($part, '/')) {
            [$range, $step] = explode('/', $part, 2);
            $step = (int) $step;
            if ($range === '*') {
                if ($current % $step === 0) return true;
            } elseif (str_contains($range, '-')) {
                [$start, $end] = explode('-', $range);
                for ($i = (int) $start; $i <= (int) $end; $i += $step) {
                    if ($i === $current) return true;
                }
            }
        } elseif (str_contains($part, '-')) {
            [$start, $end] = explode('-', $part);
            if ($current >= (int) $start && $current <= (int) $end) return true;
        } else {
            if ((int) $part === $current) return true;
        }
    }

    return false;
}

// Returns true if the cron expression matches the current minute.
function cron_is_due(string $expression): bool {
    $fields = explode(' ', trim($expression));

    if (count($fields) !== 5) {
        throw new InvalidArgumentException("Invalid cron expression: '{$expression}'");
    }

    [$minute, $hour, $dayOfMonth, $month, $dayOfWeek] = $fields;

    $now = getdate();

    return cron_field_matches($minute,     $now['minutes'])
        && cron_field_matches($hour,       $now['hours'])
        && cron_field_matches($dayOfMonth, $now['mday'])
        && cron_field_matches($month,      $now['mon'])
        && cron_field_matches($dayOfWeek,  $now['wday']);
}

// Load last run times keyed by name
$lastRuns = array_column(
    $pdo->query("SELECT name, last_run FROM schedule_runs")->fetchAll(),
    'last_run',
    'name'
);

// Current minute boundary — used to prevent double-firing
$thisMintue = date('Y-m-d H:i:00');

// Get all schedule files
$files = glob($root . '/schedules/*.php');
sort($files);

if (empty($files)) {
    echo "No schedules found.\n";
    exit;
}

foreach ($files as $file) {
    $schedule  = include $file;
    $name      = $schedule['name'];
    $cron      = $schedule['cron'];
    $task      = $schedule['task'];

    try {
        $matchesNow = cron_is_due($cron);
    } catch (InvalidArgumentException $e) {
        echo "Skipping '{$name}': " . $e->getMessage() . "\n";
        continue;
    }

    $lastRun       = $lastRuns[$name] ?? null;
    $alreadyRanThisMinute = $lastRun && strtotime($lastRun) >= strtotime($thisMintue);

    if (!$matchesNow || $alreadyRanThisMinute) {
        continue;
    }

    echo "Running: {$name} ... ";

    try {
        $task($pdo);

        $pdo->prepare("
            INSERT INTO schedule_runs (name, last_run) VALUES (?, NOW())
            ON DUPLICATE KEY UPDATE last_run = NOW()
        ")->execute([$name]);

        echo "done.\n";
        log_info("Schedule '{$name}' ran successfully.");
    } catch (Throwable $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        log_error("Schedule '{$name}' failed: " . $e->getMessage());
    }
}
