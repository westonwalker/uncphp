<?php

test('can insert a user', function ($pdo) {
    $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)')
        ->execute(['Jane Doe', 'jane@example.com', password_hash('secret', PASSWORD_DEFAULT)]);

    $user = $pdo->query("SELECT * FROM users WHERE email = 'jane@example.com'")->fetch();

    assert_not_null($user, 'user should exist');
    assert_equals('Jane Doe',         $user['name']);
    assert_equals('jane@example.com', $user['email']);
});

test('can fetch all users', function ($pdo) {
    $users = $pdo->query('SELECT * FROM users')->fetchAll();
    assert_count(1, $users, 'should have 1 user');
});

test('email must be unique', function ($pdo) {
    $threw = false;
    try {
        $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)')
            ->execute(['Duplicate', 'jane@example.com', password_hash('secret', PASSWORD_DEFAULT)]);
    } catch (PDOException) {
        $threw = true;
    }
    assert_true($threw, 'duplicate email should throw');
});

test('returns false for unknown email', function ($pdo) {
    $user = $pdo->query("SELECT * FROM users WHERE email = 'nobody@example.com'")->fetch();
    assert_false((bool) $user, 'should return no result');
});
