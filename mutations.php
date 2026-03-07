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
