<?php

return [
    'name' => 'example',
    'cron' => '0 9 * * *', // every day at 9am
    'task' => function ($pdo) {
        // Your task logic here.
        // $pdo is available for database queries.
    },
];
