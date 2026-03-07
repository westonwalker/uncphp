<?php

if ($fresh) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('TRUNCATE TABLE cities');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

$cities = [
    ['Peoria',      'IL', 'peoria-il'],
    ['Springfield', 'IL', 'springfield-il'],
    ['Bloomington', 'IL', 'bloomington-il'],
];

$stmt = $pdo->prepare('INSERT INTO cities (name, state, slug) VALUES (?, ?, ?)');

foreach ($cities as [$name, $state, $slug]) {
    $stmt->execute([$name, $state, $slug]);
}
