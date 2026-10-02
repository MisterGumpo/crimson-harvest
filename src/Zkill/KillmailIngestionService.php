<?php

declare(strict_types=1);

namespace CrimsonHarvest\Zkill;

use CrimsonHarvest\Competition\ParticipationService;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class KillmailIngestionService
{
    public function __construct(private readonly ParticipationService $participations)
    {
    }

    public function normalize(array $payload): array
    {
        foreach (['killmail_id', 'killmail_time', 'solar_system_id', 'region_id', 'attackers'] as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new InvalidArgumentException('Missing normalized killmail field: ' . $field);
            }
        }
        $time = new DateTimeImmutable((string) $payload['killmail_time'], new DateTimeZone('UTC'));
        return [
            'killmail_id' => (int) $payload['killmail_id'],
            'killmail_time' => $time->format('Y-m-d H:i:s'),
            'solar_system_id' => (int) $payload['solar_system_id'],
            'region_id' => (int) $payload['region_id'],
            'attackers' => $this->participations->eligibleAttackers((array) $payload['attackers']),
        ];
    }
}
