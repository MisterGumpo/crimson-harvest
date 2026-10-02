<?php

declare(strict_types=1);

namespace CrimsonHarvest\Tests;

use CrimsonHarvest\Competition\ParticipationService;
use CrimsonHarvest\Zkill\KillmailIngestionService;
use PHPUnit\Framework\TestCase;

final class IngestionTest extends TestCase
{
    public function testNormalizationDeduplicatesEligibleAttackers(): void
    {
        $killmail = (new KillmailIngestionService(new ParticipationService()))->normalize([
            'killmail_id' => 42,
            'killmail_time' => '2026-10-10T12:30:00Z',
            'solar_system_id' => 30002050,
            'region_id' => 10000042,
            'attackers' => [
                ['character_id' => 7, 'character_name' => 'Pilot', 'alliance_id' => 99013187],
                ['character_id' => 7, 'character_name' => 'Pilot', 'alliance_id' => 99013187],
                ['character_id' => 8, 'character_name' => 'Pilot Two', 'alliance_id' => 99013786],
                ['character_id' => 9, 'character_name' => 'Jam Tanto', 'alliance_id' => 99000001],
                ['character_id' => 10, 'character_name' => 'No Alliance'],
                ['character_id' => null, 'character_name' => 'NPC', 'alliance_id' => 99013187],
            ],
        ]);
        self::assertSame('2026-10-10 12:30:00', $killmail['killmail_time']);
        self::assertSame([7, 8], array_column($killmail['attackers'], 'character_id'));
    }
}
