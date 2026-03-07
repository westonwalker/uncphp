<?php

// Database read functions (SELECT queries).
// All functions here should only read from the database, never write.
//
// Example:
//
// function get_city(int $id): array|false {
//     global $pdo;
//     $stmt = $pdo->prepare('SELECT * FROM cities WHERE id = ?');
//     $stmt->execute([$id]);
//     return $stmt->fetch();
// }
//
// function get_cities(): array {
//     global $pdo;
//     return $pdo->query('SELECT * FROM cities ORDER BY name')->fetchAll();
// }
