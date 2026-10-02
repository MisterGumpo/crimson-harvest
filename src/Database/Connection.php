<?php

declare(strict_types=1);

namespace CrimsonHarvest\Database;

use PDO;

final class Connection
{
    public static function fromEnvironment(): PDO
    {
        $host = $_ENV['DBHOST'] ?? '127.0.0.1';
        $name = $_ENV['DBNAME'] ?? '';
        $user = $_ENV['DBUSER'] ?? '';
        $pass = $_ENV['DBPASS'] ?? '';
        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
