<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CrimsonHarvest\Database\Connection;

$pdo = Connection::fromEnvironment();
$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
$migration = file_get_contents(dirname(__DIR__) . '/database/migrations/001_data_source.sql');
$pdo->exec($schema);
$pdo->exec($migration);
fwrite(STDOUT, "Database schema and migrations applied.\n");
