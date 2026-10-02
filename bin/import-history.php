<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CrimsonHarvest\Competition\ParticipationService;
use CrimsonHarvest\Database\Connection;
use CrimsonHarvest\Zkill\EsiSystemRegionResolver;
use CrimsonHarvest\Zkill\HistoricalImporter;
use CrimsonHarvest\Zkill\KillmailStore;
use CrimsonHarvest\Zkill\R2z2PayloadParser;

$path = $argv[1] ?? null;
if ($path === null) {
	fwrite(STDERR, "Usage: php bin/import-history.php path/to/r2z2-records.json\n");
	exit(2);
}
$pdo = Connection::fromEnvironment();
$importer = new HistoricalImporter(
	new R2z2PayloadParser(new EsiSystemRegionResolver($pdo)),
	new KillmailStore($pdo, new ParticipationService()),
);
$count = $importer->importFile($path);
fwrite(STDOUT, "Imported {$count} historical killmail record(s).\n");
