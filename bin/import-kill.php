<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CrimsonHarvest\Competition\CompetitionRules;
use CrimsonHarvest\Competition\ParticipationService;
use CrimsonHarvest\Database\Connection;
use CrimsonHarvest\Zkill\EsiCharacterNameResolver;
use CrimsonHarvest\Zkill\EsiSystemRegionResolver;
use CrimsonHarvest\Zkill\KillmailStore;
use CrimsonHarvest\Zkill\R2z2PayloadParser;
use CrimsonHarvest\Zkill\ZkillboardApiClient;

$killmailId = filter_var($argv[1] ?? null, FILTER_VALIDATE_INT);
if ($killmailId === false || $killmailId === null || $killmailId < 1) {
    fwrite(STDERR, "Usage: php bin/import-kill.php <killmail-id>\n");
    exit(2);
}

$pdo = Connection::fromEnvironment();
$esi = (new ZkillboardApiClient())->killmail($killmailId);
$parser = new R2z2PayloadParser(new EsiSystemRegionResolver($pdo));
$killmail = $parser->normalizeEsiKillmail($esi, $killmailId, source: 'historical');
$rules = new CompetitionRules(
    $config->app['locations']['hagilur'],
    $config->app['locations']['metropolis'],
    $config->app['locations']['heimatar'],
);
$time = new DateTimeImmutable($killmail['killmail_time'], new DateTimeZone('UTC'));
$start = new DateTimeImmutable($config->event['starts_at'], new DateTimeZone('UTC'));
$end = new DateTimeImmutable($config->event['ends_at'], new DateTimeZone('UTC'));
if (!$rules->isWithinEvent($time, $start, $end) || !$rules->isEligibleLocation($killmail['solar_system_id'], $killmail['region_id'])) {
    fwrite(STDERR, "Killmail {$killmailId} is outside this event's time or region eligibility.\n");
    exit(1);
}

$store = new KillmailStore($pdo, new ParticipationService(), new EsiCharacterNameResolver($pdo));
$store->import($killmail);
fwrite(STDOUT, "Imported eligible killmail {$killmailId} ({$killmail['killmail_time']}, system {$killmail['solar_system_id']}).\n");
