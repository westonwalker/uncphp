<?php

// Database write functions (INSERT, UPDATE, DELETE).
// All functions here should only modify data, never just read.
//
// Example:
//
// function create_city(string $name, string $state, string $slug): int {
//     global $pdo;
//     $stmt = $pdo->prepare('INSERT INTO cities (name, state, slug) VALUES (?, ?, ?)');
//     $stmt->execute([$name, $state, $slug]);
//     return (int) $pdo->lastInsertId();
// }
//
// function delete_city(int $id): void {
//     global $pdo;
//     $pdo->prepare('DELETE FROM cities WHERE id = ?')->execute([$id]);
// }

function create_post(string $title, string $slug, string $body): int {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO posts (title, slug, body) VALUES (?, ?, ?)');
    $stmt->execute([$title, $slug, $body]);
    return (int) $pdo->lastInsertId();
}

function update_post(int $id, string $title, string $body): void {
    global $pdo;
    $pdo->prepare('UPDATE posts SET title = ?, body = ? WHERE id = ?')->execute([$title, $body, $id]);
}

function delete_post(int $id): void {
    global $pdo;
    $pdo->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
}
