# CLAUDE.md — Agent Quick Reference

## File Map

```
index.php          entry point, bootstraps everything
routes.php         route definitions, $global_middleware
middleware.php     middleware functions
actions.php        reusable helpers, no DB
queries.php        SELECT functions
mutations.php      INSERT / UPDATE / DELETE functions
command.php        CLI entry point

tests/             *_test.php files

core/
  errors.php       global exception + error handler
  test.php         test runner
  database.php     $pdo (PDO, MySQL)
  auth.php         login, logout, register, current_user, is_logged_in
  request.php      request(), query(), body(), input_file(), has(), method(), is_method(), is_ajax(), ip(), old(), flash_input()
  response.php     redirect(), back(), json(), abort()
  routing.php      get(), post(), put(), delete(), param(), dispatch()
  logger.php       log_debug/info/notice/warning/error/critical/alert/emergency()
  migrate.php      migration runner
  seed.php         seeder runner
  schedule.php     schedule runner

migrations/        *.sql files, prefix 0001_, 0002_, etc.
seeders/           *.php files, return — no naming convention required
schedules/         *.php files, return array with name/cron/task
views/             *.php page templates
views/components/  reusable partials (header.php, footer.php)
views/errors/      500.php
assets/css/        app.css (imports unc.css, unc-extend.css)
assets/js/         app.js
assets/images/
logs/              YYYY-MM-DD.log, auto-created, gitignored
```

## Routing — routes.php

```php
get('/path', 'views/page.php');
get('/city/{slug}', 'views/city.php');          // {param} capture
post('/submit', 'views/submit.php');
get('/dashboard', 'views/dashboard.php', ['auth']);  // per-route middleware

$global_middleware = [];  // runs on every request
```

CSRF runs automatically on POST/PUT/DELETE/PATCH — no setup needed.

## URL Params — in views

```php
param('slug');           // read {slug} from route
param('slug', 'default');
```

## Middleware — middleware.php

Middleware string = function name. `'auth'` calls `auth()`, `'check_role'` calls `check_role()`.

Built-in: `auth()`, `csrf()`, `csrf_token()`.

To add custom middleware: define the function in `middleware.php`, use its name in the route.

## Request — core/request.php

```php
request('key', $default)   // POST + GET merged
query('key', $default)     // $_GET only
body('key', $default)      // $_POST only
input_file('key')          // $_FILES entry or null
has('key')                 // exists in POST or GET
method()                   // 'GET', 'POST', etc.
is_method('POST')
is_ajax()
ip()
flash_input()              // flash $_POST to session for old()
old('key', $default)       // read previously flashed input
```

## Response — core/response.php

All exit after executing.

```php
redirect('/path');
redirect('/path', 301);
back();
back('/fallback');
json($data);
json($data, 404);
abort(403);
abort(403, 'Forbidden');
```

## Auth — core/auth.php

```php
login(string $email, string $password): bool
logout(): void
register(string $name, string $email, string $password): bool  // false = email taken
current_user(): array|null   // cached per request
is_logged_in(): bool
```

## Database

`$pdo` is a global PDO instance available everywhere. Use `global $pdo` inside functions.

```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user  = $stmt->fetch();          // single row
$users = $stmt->fetchAll();       // all rows
$id    = (int) $pdo->lastInsertId();
```

## Logging — core/logger.php

```php
log_debug($msg);
log_info($msg);
log_notice($msg);
log_warning($msg);
log_error($msg);
log_critical($msg);
log_alert($msg);
log_emergency($msg);
```

Controlled by `LOG_LEVEL` in `.env`. Defaults to `error`.

## Views

```php
<?php $title = 'Page Title'; ?>
<?php require 'views/components/header.php'; ?>
<!-- content -->
<?php require 'views/components/footer.php'; ?>
```

## Migrations — migrations/

Plain SQL files. Filename order = run order.

```
0001_create_users.sql
0002_add_column.sql
```

## Seeders — seeders/

```php
<?php
if ($fresh) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('TRUNCATE TABLE users');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}
$pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)')->execute([...]);
```

## Schedules — schedules/

```php
<?php
return [
    'name' => 'job_name',
    'cron' => '0 9 * * *',   // standard 5-field cron expression
    'task' => function ($pdo) {
        // task logic
    },
];
```

Cron fields: `* * * * *` = minute hour day-of-month month day-of-week.
Supports: `*`, `*/n`, `n-m`, `n,m`, `n-m/step`.

## Testing — tests/

Files named `*_test.php`. Each test receives `$pdo` pointing at the test DB.

```php
test('description', function ($pdo) {
    // insert fixture data, then assert
    assert_equals($expected, $actual);
});
```

Helpers: `assert_true`, `assert_false`, `assert_equals`, `assert_not_null`, `assert_null`, `assert_count`.

Test DB is created fresh, all migrations run, then dropped on completion.

## Commands

```bash
php command.php migrate
php command.php migrate --fresh
php command.php seed
php command.php seed <name>
php command.php seed <name> --fresh
php command.php schedule
php command.php test
```

## CSS

- `assets/css/unc.css` — utility class library (do not edit)
- `assets/css/unc-extend.css` — add all custom classes and token overrides here
- Both are imported via `assets/css/app.css`

Use `unc.css` utility classes directly in HTML. Add any custom component classes to `unc-extend.css`.

## JavaScript

- Shared JS (used across multiple pages) → `assets/js/app.js`
- Page-specific JS → inline in the relevant `views/*.php` or `views/components/*.php` file

## Conventions

- New routes → `routes.php`
- New SELECT functions → `queries.php`
- New INSERT/UPDATE/DELETE functions → `mutations.php`
- New helper functions (no DB) → `actions.php`
- New middleware → `middleware.php`, use function name as string in route
- New DB table → new migration file, zero-padded prefix
- New page → new file in `views/`, register route in `routes.php`
- New reusable partial → `views/components/`

## ENV Variables

```
DB_HOST, DB_NAME, DB_TEST_NAME, DB_USER, DB_PASS
LOG_LEVEL=debug|info|notice|warning|error|critical|alert|emergency
APP_ENV=local|production
```
