<?php

declare(strict_types=1);

return [
    'appName' => 'calfords',
    'database' => [
        'host' => getenv('DB_HOST'),
        'dbname' => 'fas-domain',
        'username' => getenv('DB_USER'),
        'password' => getenv('DB_PASSWORD'),
    ],
    'elasticsearch' => [
        'host' => getenv('ELASTICSEARCH_HOST'),
        'port' => getenv('ELASTICSEARCH_PORT'),
    ],
];
