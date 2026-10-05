<?php

declare(strict_types=1);

namespace CrimsonHarvest\Tests;

use CrimsonHarvest\Competition\ParticipationService;
use CrimsonHarvest\Zkill\KillmailIngestionService;
use CrimsonHarvest\Zkill\KillmailStore;
use PDO;
use PDOStatement;
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

    public function testStorePersistsFinalBlowAsIntegers(): void
    {
        $pdo = $this->createMock(PDO::class);
        $killmailStatement = $this->createMock(PDOStatement::class);
        $attackerStatement = $this->createMock(PDOStatement::class);
        $pdo->expects(self::once())->method('beginTransaction')->willReturn(true);
        $pdo->expects(self::exactly(2))->method('prepare')->willReturnOnConsecutiveCalls($killmailStatement, $attackerStatement);
        $pdo->expects(self::once())->method('commit')->willReturn(true);
        $pdo->expects(self::never())->method('rollBack');
        $killmailStatement->expects(self::once())->method('execute')->willReturn(true);
        $finalBlows = [];
        $attackerStatement->expects(self::exactly(3))->method('execute')->willReturnCallback(
            static function (array $parameters) use (&$finalBlows): bool {
                $finalBlows[] = $parameters[7];
                return true;
            },
        );

        (new KillmailStore($pdo, new ParticipationService()))->import([
            'killmail_id' => 42,
            'killmail_time' => '2026-10-10 12:30:00',
            'solar_system_id' => 30002050,
            'region_id' => 10000042,
            'victim_character_id' => null,
            'victim_ship_type_id' => null,
            'zkill_url' => 'https://zkillboard.com/kill/42/',
            'total_value' => null,
            'attackers' => [
                ['character_id' => 7, 'alliance_id' => 99013187, 'final_blow' => false],
                ['character_id' => 8, 'alliance_id' => 99013786, 'final_blow' => true],
                ['character_id' => 9, 'alliance_id' => 99013187],
            ],
        ]);

        self::assertSame([0, 1, 0], $finalBlows);
    }
}
