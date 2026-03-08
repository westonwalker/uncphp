<?php

// Middleware that runs on every request.
// Add middleware names from the $middleware_map in core/routing.php.
$global_middleware = [];

// --- Routes ---
// get(uri, view, middleware)
// post(uri, view, middleware)
// put(uri, view, middleware)
// delete(uri, view, middleware)
// get('/dashboard', 'views/dashboard.php', ['auth'])
//
// Use {param} in the URI to capture URL segments.
// Access them in views with param('name').

get('/', 'views/home.php');
post('/search', 'views/search_results.php');

get('/posts/create', 'views/posts/create.php');
post('/posts/create', 'views/posts/create.php');

get('/posts/{slug}', 'views/posts/show.php');

get('/posts/{slug}/edit', 'views/posts/edit.php');
post('/posts/{slug}/edit', 'views/posts/edit.php');
