<?php

declare(strict_types=1);

namespace CrimsonHarvest\Zkill;

use RuntimeException;

final class ZkillboardApiClient
{
    public function __construct(
        private readonly string $baseUrl = 'https://zkillboard.com/api',
        private readonly string $userAgent = 'CRIMSON-HARVEST/1.0 (+https://eve-crimson-pvp.development)',
    ) {
    }

    public function killmail(int $killmailId): array
    {
        if ($killmailId < 1) {
            throw new RuntimeException('Killmail ID must be positive.');
        }
        $url = $this->baseUrl . '/kills/killID/' . $killmailId . '/';
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'timeout' => 30,
            'ignore_errors' => true,
            'header' => "User-Agent: {$this->userAgent}\r\nAccept: application/json\r\n",
        ]]);
        $body = file_get_contents($url, false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                $status = (int) $matches[1];
                break;
            }
        }
        if ($body === false || $status !== 200) {
            throw new RuntimeException('zKillboard API returned HTTP ' . $status . ' for killmail ' . $killmailId . '.');
        }
        $records = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($records) || !isset($records[0]) || !is_array($records[0])) {
            throw new RuntimeException('zKillboard API did not return a killmail record.');
        }
        if ((int) ($records[0]['killmail_id'] ?? 0) !== $killmailId) {
            throw new RuntimeException('zKillboard API returned a different killmail ID.');
        }
        return $records[0];
    }
}
