<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CrimsonHarvest\Database\Connection;

$pdo = Connection::fromEnvironment();
$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
$pdo->exec($schema);
$pdo->exec(file_get_contents(dirname(__DIR__) . '/database/migrations/001_data_source.sql'));
$pdo->prepare('INSERT INTO events (name, slug, starts_at, ends_at, timezone) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), starts_at = VALUES(starts_at), ends_at = VALUES(ends_at), timezone = VALUES(timezone)')
    ->execute([$config->event['name'], $config->event['slug'], $config->event['starts_at'], $config->event['ends_at'], $config->event['timezone']]);

$pdo->beginTransaction();
try {
    $pdo->exec("DELETE FROM excluded_killmails WHERE reason = 'demo exclusion'");
    $pdo->exec("DELETE FROM excluded_characters WHERE reason = 'demo exclusion'");
    $pdo->exec("DELETE FROM killmails WHERE data_source = 'demo'");
    $killmail = $pdo->prepare('INSERT INTO killmails (killmail_id, killmail_time, solar_system_id, region_id, victim_character_name, victim_ship_name, zkill_url, data_source) VALUES (?, ?, ?, ?, ?, ?, ?, \'demo\')');
    $attacker = $pdo->prepare('INSERT INTO killmail_attackers (killmail_id, character_id, character_name, final_blow) VALUES (?, ?, ?, ?)');
    for ($index = 1; $index <= 60; $index++) {
        $killmailId = 900000000 + $index;
        $systemId = $index <= 30 ? 30002050 : 30000001;
        $regionId = $index <= 45 ? 10000042 : 10000030;
        $killmail->execute([$killmailId, '2026-10-10 12:00:00', $systemId, $regionId, 'Demo Victim ' . $index, 'Hurricane', 'https://zkillboard.com/kill/' . $killmailId . '/']);
        $pilotCount = 1 + ($index % 5);
        for ($pilot = 1; $pilot <= $pilotCount; $pilot++) {
            $characterId = (($index + $pilot) % 60) + 1;
            $attacker->execute([$killmailId, $characterId, 'Demo Pilot ' . $characterId, $pilot === $pilotCount]);
        }
    }
    for ($extra = 61; $extra <= 70; $extra++) {
        $killmailId = 900000000 + $extra;
        $killmail->execute([$killmailId, '2026-10-15 12:00:00', 30002050, 10000042, 'Cross Contest Victim ' . $extra, 'Naglfar', 'https://zkillboard.com/kill/' . $killmailId . '/']);
        $attacker->execute([$killmailId, 1, 'Demo Pilot 1', true]);
        $attacker->execute([$killmailId, 2, 'Demo Pilot 2', false]);
    }
    $pdo->exec('INSERT INTO excluded_characters (character_id, reason) VALUES (60, "demo exclusion") ON DUPLICATE KEY UPDATE reason = VALUES(reason)');
    $pdo->exec('INSERT INTO excluded_killmails (killmail_id, reason) VALUES (900000001, "demo exclusion") ON DUPLICATE KEY UPDATE reason = VALUES(reason)');
    $pdo->commit();
    fwrite(STDOUT, "Demo data loaded: 70 killmails across 60 pilots.\n");
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}
