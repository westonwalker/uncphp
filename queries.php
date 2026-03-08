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

function get_posts(): array {
    global $pdo;
    return $pdo->query('SELECT * FROM posts ORDER BY created_at DESC')->fetchAll();
}

function get_post_by_slug(string $slug): array|false {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function search_posts(string $query): array {
    global $pdo;
    if ($query === '') return get_posts();
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE title LIKE ? ORDER BY created_at DESC');
    $stmt->execute(['%' . $query . '%']);
    return $stmt->fetchAll();
}
