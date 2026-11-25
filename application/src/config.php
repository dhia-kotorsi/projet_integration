<?php

function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: '192.168.56.13';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'taches_db';
        $user = getenv('DB_USER') ?: 'app_user';
        $password = getenv('DB_PASSWORD');

        if ($password === false || $password === '') {
            throw new RuntimeException('DB_PASSWORD is not configured');
        }

        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    return $pdo;
}
