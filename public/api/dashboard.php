<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use CrimsonHarvest\Competition\LeaderboardService;
use CrimsonHarvest\Database\Connection;
use CrimsonHarvest\Repository\DashboardRepository;
use CrimsonHarvest\Zkill\EsiShipTypeNameResolver;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $pdo = Connection::fromEnvironment();
    $payload = (new DashboardRepository($pdo, new LeaderboardService(), new EsiShipTypeNameResolver($pdo)))->dashboard($config->event, $config->app['locations']);
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $start = new DateTimeImmutable($config->event['starts_at'], new DateTimeZone('UTC'));
    $end = new DateTimeImmutable($config->event['ends_at'], new DateTimeZone('UTC'));
    $status = $now < $start ? 'before' : ($now < $end ? 'active' : 'ended');
    echo json_encode(['event' => ['name' => $config->event['name'], 'startsAt' => $start->format(DateTimeInterface::ATOM), 'endsAt' => $end->format(DateTimeInterface::ATOM), 'status' => $status], ...$payload, 'generatedAt' => $now->format(DateTimeInterface::ATOM)], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(503);
    echo json_encode(['error' => 'Dashboard data is temporarily unavailable.', 'generatedAt' => gmdate('c')]);
}
