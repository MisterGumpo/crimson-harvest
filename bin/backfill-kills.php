<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CrimsonHarvest\Competition\CompetitionRules;
use CrimsonHarvest\Competition\ParticipationService;
use CrimsonHarvest\Database\Connection;
use CrimsonHarvest\Zkill\EsiCharacterNameResolver;
use CrimsonHarvest\Zkill\EsiSystemRegionResolver;
use CrimsonHarvest\Zkill\HistoricalImporter;
use CrimsonHarvest\Zkill\KillmailStore;
use CrimsonHarvest\Zkill\R2z2PayloadParser;

if (($argv[1] ?? null) === '--help') {
    fwrite(STDOUT, "Usage: php bin/backfill-kills.php [YYYY-MM-DD [YYYY-MM-DD-exclusive-end]]\nDefaults: event start through the current UTC time, capped at event end.\nImports published daily archives without changing the live feed cursor.\n");
    exit(0);
}

$temporaryPath = null;
$exitCode = 0;
try {
    $utc = new DateTimeZone('UTC');
    $eventStart = new DateTimeImmutable($config->event['starts_at'], $utc);
    $eventEnd = new DateTimeImmutable($config->event['ends_at'], $utc);
    $parseDate = static function (string $value) use ($utc): DateTimeImmutable {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $utc);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Expected a valid UTC date in YYYY-MM-DD format: ' . $value);
        }
        return $date;
    };
    if ($argc > 3) {
        throw new InvalidArgumentException('Usage: php bin/backfill-kills.php [YYYY-MM-DD [YYYY-MM-DD-exclusive-end]]');
    }
    $now = new DateTimeImmutable('now', $utc);
    $start = isset($argv[1]) ? $parseDate($argv[1]) : $eventStart;
    $end = isset($argv[2]) ? $parseDate($argv[2]) : min($now, $eventEnd);
    if ($start < $eventStart || $end > $eventEnd || $end > $now || $start >= $end) {
        throw new InvalidArgumentException('The date range must be inside the configured event and not extend into the future.');
    }

    $pdo = Connection::fromEnvironment();
    $rules = new CompetitionRules(
        $config->app['locations']['hagilur'],
        $config->app['locations']['metropolis'],
        $config->app['locations']['heimatar'],
    );
    $importer = new HistoricalImporter(
        new R2z2PayloadParser(new EsiSystemRegionResolver($pdo)),
        new KillmailStore($pdo, new ParticipationService(), new EsiCharacterNameResolver($pdo)),
    );
    $userAgent = 'CrimsonHarvest/1.0 (+' . $config->app['url'] . ')';
    $context = stream_context_create(['http' => [
        'method' => 'GET',
        'timeout' => 120,
        'ignore_errors' => true,
        'header' => "User-Agent: {$userAgent}\r\nAccept: application/json\r\n",
    ]]);
    fwrite(STDOUT, 'Backfilling ' . $start->format(DATE_ATOM) . ' up to ' . $end->format(DATE_ATOM) . " (exclusive).\n");
    $total = 0;
    for ($day = $start->setTime(0, 0); $day < $end; $day = $day->modify('+1 day')) {
        $url = 'https://r2z2.zkillboard.com/history/raw/' . $day->format('Ymd') . '.json';
        fwrite(STDOUT, 'Downloading ' . $day->format('Y-m-d') . " UTC...\n");
        $input = @fopen($url, 'rb', false, $context);
        if ($input === false) {
            throw new RuntimeException('Unable to download ' . $url . '. Check outbound HTTPS, DNS and CA certificates.');
        }
        $output = null;
        try {
            $status = 0;
            foreach (stream_get_meta_data($input)['wrapper_data'] ?? [] as $header) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                    $status = (int) $matches[1];
                }
            }
            if ($status !== 200) {
                throw new RuntimeException('Archive returned HTTP ' . $status . ' for ' . $day->format('Y-m-d') . '. It may not be published yet. Earlier days remain imported; rerun this date range later.');
            }
            $temporaryPath = tempnam(sys_get_temp_dir(), 'crimson-history-');
            if ($temporaryPath === false) {
                $temporaryPath = null;
                throw new RuntimeException('Unable to create a temporary history file.');
            }
            $output = fopen($temporaryPath, 'wb');
            if ($output === false || stream_copy_to_stream($input, $output) === false || stream_get_meta_data($input)['timed_out']) {
                throw new RuntimeException('Archive download failed or timed out: ' . $url);
            }
        } finally {
            fclose($input);
            if (is_resource($output)) {
                fclose($output);
            }
        }
        $count = $importer->importRawFile($temporaryPath, $rules, $start, $end);
        unlink($temporaryPath);
        $temporaryPath = null;
        $total += $count;
        fwrite(STDOUT, $day->format('Y-m-d') . ': processed ' . $count . " qualifying killmail(s).\n");
    }
    fwrite(STDOUT, 'Finished published archives: processed ' . $total . " qualifying killmail(s). Existing killmail IDs are reused; the live cursor was not changed.\nArchives can lag or receive late submissions; rerun later to catch those records.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    $exitCode = 1;
} finally {
    if ($temporaryPath !== null) {
        unlink($temporaryPath);
    }
}
exit($exitCode);