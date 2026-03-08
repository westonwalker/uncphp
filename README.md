# Unc PHP

A simple PHP app. No framework, low abstraction.

## Requirements

- PHP 8.1+
- MySQL
- Apache with `mod_rewrite` enabled

## Setup

1. Copy `.env.example` to `.env` and fill in your database credentials:

    ```
    DB_HOST=localhost
    DB_NAME=your_database
    DB_USER=root
    DB_PASS=
    ```

2. Run migrations (this will create the database if it doesn't exist):

    ```bash
    php command.php migrate
    ```

3. Point your local web server at the project root. Apache will use `.htaccess` to route all requests through `index.php`.

---

## Project Structure

```
/
├── index.php          # Entry point — bootstraps the app and dispatches requests
├── command.php        # CLI entry point — all commands run through here
├── routes.php         # Route definitions and global middleware
├── middleware.php     # Middleware functions
├── actions.php        # Reusable helpers (no database)
├── queries.php        # Database read functions (SELECT)
├── mutations.php      # Database write functions (INSERT, UPDATE, DELETE)
├── migrations/        # SQL migration files
├── seeders/           # PHP seeder files
├── core/              # Framework-level files
│   ├── auth.php       # login, logout, register, current_user, is_logged_in
│   ├── database.php   # PDO MySQL connection (uses $_ENV)
│   ├── errors.php     # Global error and exception handler
│   ├── logger.php     # File-based logger
│   ├── migrate.php    # Migration runner logic
│   ├── request.php    # Request input helpers
│   ├── response.php   # Response helpers (redirect, json, abort)
│   ├── routing.php    # Route registration, resolution, and dispatch
│   ├── schedule.php   # Schedule runner logic
│   └── seed.php       # Seeder runner logic
├── schedules/         # Scheduled task files
├── views/             # Page templates
│   └── components/    # Reusable partials (header, footer, etc.)
└── assets/            # Static files
    ├── css/
    ├── js/
    └── images/
```

---

## Routing

Routes are defined in `routes.php` using helper functions:

```php
get('/', 'views/home.php');
get('/city/{slug}', 'views/city.php');
post('/subscribe', 'views/subscribe.php');
get('/dashboard', 'views/dashboard.php', ['auth']);
```

The third argument is an optional list of middleware to run before the view is loaded.

**URL parameters** — use `{name}` placeholders in the URI and read them in the view with `param()`:

```php
// routes.php
get('/city/{slug}', 'views/city.php');

// views/city.php
$slug = param('slug');
```

**Global middleware** — runs on every request. Define it at the top of `routes.php`:

```php
$global_middleware = ['auth'];
```

**CSRF** is verified automatically on all `POST`, `PUT`, `DELETE`, and `PATCH` requests. No setup needed. Always include the token in your forms:

```html
<form method="POST" action="/subscribe">
	<input type="hidden" name="csrf_token" value="<?= csrf_token() ?>" />
	...
</form>
```

---

## Middleware

Middleware functions live in `middleware.php`. The string you pass in a route **is the function name** — no registration needed.

Built-in middleware:

| String   | Function | Description                                                |
| -------- | -------- | ---------------------------------------------------------- |
| `'auth'` | `auth()` | Redirects to `/login` if the user is not logged in         |
| `'csrf'` | `csrf()` | Validates `$_POST['csrf_token']`, dies with 403 on failure |

**Adding custom middleware:**

1. Define the function in `middleware.php`:
    ```php
    function check_user_role() {
        if ($_SESSION['role'] !== 'admin') {
            http_response_code(403);
            require 'views/403.php';
            exit;
        }
    }
    ```
2. Use the function name as the string in any route — no other steps:
    ```php
    get('/admin', 'views/admin.php', ['auth', 'check_user_role']);
    ```

---

## Views

Views are plain PHP files. Include the header and footer components at the top and bottom. Set `$title` before the header include to control the page `<title>`:

```php
<?php $title = 'About'; ?>
<?php require 'views/components/header.php'; ?>

<h1>About</h1>

<?php require 'views/components/footer.php'; ?>
```

---

## Actions, Queries, and Mutations

All three files are required on every request and their functions are available everywhere.

**`actions.php`** — reusable helpers that do not touch the database:

```php
function format_date(string $date): string {
    return date('F j, Y', strtotime($date));
}
```

**`queries.php`** — database read functions (SELECT only):

```php
function get_user(int $id): array|false {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_users(): array {
    global $pdo;
    return $pdo->query('SELECT * FROM users ORDER BY name')->fetchAll();
}
```

**`mutations.php`** — database write functions (INSERT, UPDATE, DELETE):

```php
function update_user_name(int $id, string $name): void {
    global $pdo;
    $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $id]);
}

function delete_user(int $id): void {
    global $pdo;
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
}
```

---

## Migrations

Migration files live in `migrations/` and are plain SQL. Name them with a zero-padded number prefix so they run in order:

```
migrations/
    0001_create_users.sql
    0002_add_column_to_users.sql
```

**Run pending migrations:**

```bash
php command.php migrate
```

**Drop the database and re-run everything from scratch:**

```bash
php command.php migrate --fresh
```

The `migrations` table tracks which files have already been applied. Only new files will run on subsequent calls. If a migration fails, it stops immediately and nothing is recorded, so you can fix the SQL and re-run.

There are no down migrations. To reverse a change, create a new migration file.

---

## Commands

All CLI commands run through `command.php`:

```bash
php command.php <command> [target] [--flags]
```

| Command                               | Description                                     |
| ------------------------------------- | ----------------------------------------------- |
| `php command.php migrate`             | Run all pending migrations                      |
| `php command.php migrate --fresh`     | Drop the database and re-run all migrations     |
| `php command.php seed`                | Run all seeders                                 |
| `php command.php seed <name>`         | Run a single seeder by name                     |
| `php command.php seed <name> --fresh` | Truncate the table then run the seeder          |
| `php command.php schedule`            | Run all due scheduled tasks                     |
| `php command.php test`                | Run all tests against a temporary test database |

**Adding a new command:**

1. Create a PHP file in `core/`, e.g. `core/mycommand.php`
2. Register it in the `$commands` array in `command.php`:
    ```php
    $commands = [
        'migrate'   => __DIR__ . '/core/migrate.php',
        'seed'      => __DIR__ . '/core/seed.php',
        'mycommand' => __DIR__ . '/core/mycommand.php',
    ];
    ```
3. Run it with `php command.php mycommand`

Inside a command file, `$argv` is already stripped of the command name so you can read flags and arguments from index 1 onward as normal.

---

## Schedules

Scheduled tasks live in `schedules/`. Each file returns an array with a name, a cron expression, and a task closure. `$pdo` is passed into the closure automatically.

```php
// schedules/send_newsletter.php
return [
    'name' => 'send_newsletter',
    'cron' => '0 9 * * 1',   // every Monday at 9am
    'task' => function ($pdo) {
        $cities = $pdo->query('SELECT * FROM cities')->fetchAll();
        // ...
    },
];
```

**Cron expression syntax** (5 fields: minute, hour, day-of-month, month, day-of-week):

| Expression       | Description                |
| ---------------- | -------------------------- |
| `* * * * *`      | Every minute               |
| `0 * * * *`      | Every hour                 |
| `0 9 * * *`      | Daily at 9am               |
| `0 9 * * 1`      | Every Monday at 9am        |
| `*/15 * * * *`   | Every 15 minutes           |
| `0 9,17 * * 1-5` | 9am and 5pm, weekdays only |

Supports `*`, `*/n`, `n-m`, `n,m`, and `n-m/step` in all five fields.

**Server setup** — add one crontab entry that runs every minute:

```
* * * * * php /path/to/command.php schedule
```

The `schedule_runs` table tracks the last time each task ran, preventing double-firing if the cron ticks more than once in the same minute.

---

## Error Handling

Errors and unhandled exceptions are caught globally by `core/errors.php`, which is loaded first in `index.php`. Set `APP_ENV` in `.env` to control the behaviour:

| `APP_ENV`    | Behaviour                                                               |
| ------------ | ----------------------------------------------------------------------- |
| `local`      | Full error page — exception class, message, file, line, and stack trace |
| `production` | Clean `views/errors/500.php` page shown to the user                     |

Errors are always logged via `log_error()` regardless of environment.

**Local dev error page:**

All the details you need — exception type, message, location, and a full stack trace rendered in a readable dark-themed page.

**Customising the production error page:**

Edit `views/errors/500.php` to match your app's design. It uses the standard header/footer components.

**`APP_ENV` in `.env`:**

```
APP_ENV=local      # development
APP_ENV=production # live server
```

---

## Request

Request helpers live in `core/request.php` and are available everywhere.

```php
// All input (POST + GET merged), or a single key
request()           // returns all input as array
request('email')    // returns $_POST['email'] ?? $_GET['email'] ?? null
request('role', 'guest') // with default

// GET or POST only
query('page', 1)    // $_GET['page']
body('email')       // $_POST['email']

// Files
input_file('avatar') // returns $_FILES['avatar'] or null

// Check existence
has('email')        // true if key exists in POST or GET

// Request info
method()            // 'GET', 'POST', etc.
is_method('POST')   // true/false
is_ajax()           // true if X-Requested-With: XMLHttpRequest
ip()                // client IP address
```

**Repopulating forms with `old()`** — after a failed form submission, flash the input to the session before redirecting back. On the next request `old()` reads those values:

```php
// POST handler view
if (!$valid) {
    flash_input();
    back();
}

// Form view
<input type="email" name="email" value="<?= old('email') ?>">
```

---

## Response

Response helpers live in `core/response.php` and are available everywhere. All of them stop execution.

```php
redirect('/dashboard');          // 302 redirect
redirect('/login', 301);         // with custom status

back();                          // redirect to previous page
back('/fallback');               // fallback if no referrer

json(['ok' => true]);            // 200 JSON response
json(['error' => 'Not found'], 404);

abort(403);                      // empty error response
abort(403, 'Forbidden');         // with message
```

---

## Authentication

Auth functions live in `core/auth.php` and are available everywhere.

**Registering a user:**

```php
$success = register('Jane Doe', 'jane@example.com', 'password');
// Returns false if the email is already taken
```

**Logging in:**

```php
$success = login($email, $password);
// Returns false on invalid credentials

if (!$success) {
    // show error
}

header('Location: /dashboard');
exit;
```

**Logging out:**

```php
logout(); // deletes the DB session and destroys $_SESSION
header('Location: /login');
exit;
```

**Getting the current user:**

```php
$user = current_user(); // returns user array or null
echo $user['name'];
```

**Checking login status:**

```php
if (is_logged_in()) { ... }
```

**Protecting routes** — add the `auth` middleware to any route in `routes.php`:

```php
get('/dashboard', 'views/dashboard.php', ['auth']);
```

`auth()` calls `is_logged_in()` and redirects to `/login` if the user is not authenticated.

**How sessions work:**
On login, a secure random token is generated and stored in both `$_SESSION` and the `sessions` DB table. On every request, `current_user()` looks up that token in the DB to identify the user (result is cached for the request). Logging out deletes the row from `sessions`, which immediately invalidates the session even if the cookie persists.

The `sessions` table also tracks `ip_address`, `user_agent`, and `last_active` per session.

---

## Logging

Log files are written to `logs/YYYY-MM-DD.log`, one file per day. Set `LOG_LEVEL` in `.env` to control the minimum level that gets written. Defaults to `error` if not set.

Levels from least to most severe: `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`

```php
log_debug('Query took 200ms');
log_info('User subscribed to Peoria newsletter');
log_warning('Thumbnail missing for event');
log_error('Failed to connect to database');
log_critical('Unhandled exception in checkout');
```

---

## Testing

Tests live in `tests/` and are named `*_test.php`. Run them with:

```bash
php command.php test
```

The test runner:

1. Creates a fresh MySQL database (`DB_TEST_NAME` from `.env`)
2. Runs all migrations against it
3. Loads `actions.php`, `queries.php`, `mutations.php`
4. Runs all test files
5. Drops the database when done

Each test gets `$pdo` pointing at the test database. Tests are isolated — insert your own fixture data inline.

**Writing tests:**

```php
// tests/users_test.php

test('can insert and fetch a user', function ($pdo) {
    $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)')
        ->execute(['Jane Doe', 'jane@example.com', password_hash('secret', PASSWORD_DEFAULT)]);

    $user = $pdo->query("SELECT * FROM users WHERE email = 'jane@example.com'")->fetch();

    assert_not_null($user);
    assert_equals('Jane Doe', $user['name']);
});
```

**Assertion helpers:**

| Helper                                    | Description         |
| ----------------------------------------- | ------------------- |
| `assert_true($condition, $msg)`           | Fails if false      |
| `assert_false($condition, $msg)`          | Fails if true       |
| `assert_equals($expected, $actual, $msg)` | Strict equality     |
| `assert_not_null($value, $msg)`           | Fails if null       |
| `assert_null($value, $msg)`               | Fails if not null   |
| `assert_count($expected, $array, $msg)`   | Checks array length |

If any test fails, the command exits with code `1` — suitable for CI pipelines.

**`DB_TEST_NAME` in `.env`:**

```
DB_TEST_NAME=funlocal_test
```

---

## Database

`core/database.php` exposes a `$pdo` variable available globally. Use it directly in actions or views:

```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
```
