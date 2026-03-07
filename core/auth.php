<?php

// Attempt to log in with email and password.
// Returns true on success, false on invalid credentials.
function login(string $email, string $password): bool {
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    $token = bin2hex(random_bytes(32));

    $pdo->prepare('INSERT INTO sessions (id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)')
        ->execute([
            $token,
            $user['id'],
            $_SERVER['REMOTE_ADDR']     ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

    $_SESSION['auth_token'] = $token;

    return true;
}

// Log out the current user, deleting their session from the DB.
function logout(): void {
    global $pdo;

    if (!empty($_SESSION['auth_token'])) {
        $pdo->prepare('DELETE FROM sessions WHERE id = ?')
            ->execute([$_SESSION['auth_token']]);
    }

    $_SESSION = [];
    session_destroy();
}

// Register a new user.
// Returns true on success, false if the email is already taken.
function register(string $name, string $email, string $password): bool {
    global $pdo;

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return false;
    }

    $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)')
        ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);

    return true;
}

// Returns the currently logged-in user as an array, or null if not logged in.
// Result is cached for the lifetime of the request.
function current_user(): array|null {
    global $pdo;
    static $cache = ['fetched' => false, 'user' => null];

    if ($cache['fetched']) {
        return $cache['user'];
    }

    $cache['fetched'] = true;

    if (empty($_SESSION['auth_token'])) {
        return null;
    }

    $stmt = $pdo->prepare('
        SELECT u.* FROM users u
        JOIN sessions s ON s.user_id = u.id
        WHERE s.id = ?
    ');
    $stmt->execute([$_SESSION['auth_token']]);
    $cache['user'] = $stmt->fetch() ?: null;

    if ($cache['user']) {
        $pdo->prepare('UPDATE sessions SET last_active = NOW() WHERE id = ?')
            ->execute([$_SESSION['auth_token']]);
    }

    return $cache['user'];
}

// Returns true if a user is currently logged in.
function is_logged_in(): bool {
    return current_user() !== null;
}
