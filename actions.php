<?php

// Reusable helper functions that do NOT touch the database.
// For database reads use queries.php, for writes use mutations.php.
//
// Example:
//
// function format_date(string $date): string {
//     return date('F j, Y', strtotime($date));
// }

function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function format_date(string $date): string {
    return date('F j, Y', strtotime($date));
}
