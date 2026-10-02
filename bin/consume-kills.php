<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CrimsonHarvest\Competition\CompetitionRules;
use CrimsonHarvest\Competition\ParticipationService;
use CrimsonHarvest\Database\Connection;
use CrimsonHarvest\Zkill\EsiSystemRegionResolver;
use CrimsonHarvest\Zkill\EsiCharacterNameResolver;
use CrimsonHarvest\Zkill\KillmailStore;
use CrimsonHarvest\Zkill\R2z2Client;
use CrimsonHarvest\Zkill\R2z2PayloadParser;

$pdo = Connection::fromEnvironment();
$store = new KillmailStore($pdo, new ParticipationService(), new EsiCharacterNameResolver($pdo));
$client = new R2z2Client(userAgent: 'CrimsonHarvest/1.0 (+'.($config->app['url'] ?? 'http://localhost').')');
$parser = new R2z2PayloadParser(new EsiSystemRegionResolver($pdo));
$rules = new CompetitionRules(
	$config->app['locations']['hagilur'],
	$config->app['locations']['metropolis'],
	$config->app['locations']['heimatar'],
);
$start = new DateTimeImmutable($config->event['starts_at'], new DateTimeZone('UTC'));
$end = new DateTimeImmutable($config->event['ends_at'], new DateTimeZone('UTC'));
$sequence = $store->cursor('r2z2') ?? $client->startingSequence();
$maxMessages = max(0, (int) (getenv('CONSUMER_MAX_MESSAGES') ?: 0));
$processedMessages = 0;

fwrite(STDOUT, "CRIMSON HARVEST R2Z2 consumer starting at sequence {$sequence}.\n");
$store->heartbeat('r2z2', $sequence);

while (true) {
	try {
		$package = $client->fetchSequence($sequence);
		if ($package === null) {
			$store->heartbeat('r2z2');
			sleep(6);
			continue;
		}
		$killmail = $parser->normalize($package, $sequence);
		$killmailTime = new DateTimeImmutable($killmail['killmail_time'], new DateTimeZone('UTC'));
		if ($killmailTime >= $start && $killmailTime < $end && $rules->isEligibleLocation($killmail['solar_system_id'], $killmail['region_id'])) {
			$store->import($killmail);
			fwrite(STDOUT, "Imported killmail {$killmail['killmail_id']} (sequence {$sequence}).\n");
		}
		$store->saveCursor('r2z2', $sequence);
		$sequence++;
		$processedMessages++;
		if ($maxMessages > 0 && $processedMessages >= $maxMessages) {
			fwrite(STDOUT, "Consumer stopped after {$processedMessages} message(s).\n");
			break;
		}
		usleep(100000);
	} catch (Throwable $exception) {
		fwrite(STDERR, '[' . gmdate('c') . '] ' . $exception->getMessage() . "\n");
		sleep(10);
	}
}
