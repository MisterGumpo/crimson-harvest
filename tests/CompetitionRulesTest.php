<?php

declare(strict_types=1);

namespace CrimsonHarvest\Tests;

use CrimsonHarvest\Competition\CompetitionRules;
use CrimsonHarvest\Competition\LeaderboardService;
use CrimsonHarvest\Competition\ParticipationService;
use PHPUnit\Framework\TestCase;

final class CompetitionRulesTest extends TestCase
{
    public function testAllUniquePlayerAttackersReceiveOneCredit(): void
    {
        $attackers = (new ParticipationService())->eligibleAttackers([
            ['character_id' => 10, 'character_name' => 'Alpha', 'alliance_id' => 99013187, 'final_blow' => true],
            ['character_id' => 11, 'character_name' => 'Bravo', 'alliance_id' => 99013786, 'final_blow' => false],
        ]);
        self::assertCount(2, $attackers);
    }

    public function testNpcAndDuplicateAttackersAreIgnored(): void
    {
        $attackers = (new ParticipationService())->eligibleAttackers([
            ['character_id' => 10, 'character_name' => 'Alpha', 'alliance_id' => 99013187],
            ['character_id' => 10, 'character_name' => 'Alpha', 'alliance_id' => 99013187],
            ['character_id' => null, 'character_name' => 'NPC', 'alliance_id' => 99013187],
            ['character_id' => 'not-an-id', 'character_name' => 'NPC', 'alliance_id' => 99013187],
        ]);
        self::assertCount(1, $attackers);
    }

    public function testFinalBlowReceivesNoAdditionalCredit(): void
    {
        $participations = [
            ['killmail_id' => 1, 'character_id' => 10, 'character_name' => 'Alpha'],
        ];
        $board = (new LeaderboardService())->build($participations);
        self::assertSame(1, $board[0]['kills']);
    }

    public function testLeaderboardUsesMostRecentCorporationAndAlliance(): void
    {
        $board = (new LeaderboardService())->build([
            ['killmail_id' => 1, 'character_id' => 10, 'character_name' => 'Alpha', 'corporation_id' => 100, 'alliance_id' => 200, 'killmail_time' => '2026-10-01 10:00:00'],
            ['killmail_id' => 2, 'character_id' => 10, 'character_name' => 'Alpha', 'corporation_id' => 101, 'alliance_id' => 201, 'killmail_time' => '2026-10-02 10:00:00'],
        ]);
        self::assertSame(101, $board[0]['corporation_id']);
        self::assertSame(201, $board[0]['alliance_id']);
    }

    public function testHagilurWinnersAreRemovedFromRegionalAndRaffleStandings(): void
    {
        $service = new LeaderboardService();
        $hagilur = $service->build([
            ['killmail_id' => 1, 'character_id' => 1, 'character_name' => 'Alpha'],
            ['killmail_id' => 2, 'character_id' => 2, 'character_name' => 'Bravo'],
            ['killmail_id' => 3, 'character_id' => 3, 'character_name' => 'Charlie'],
        ]);
        $regional = $service->build([
            ['killmail_id' => 1, 'character_id' => 1, 'character_name' => 'Alpha'],
            ['killmail_id' => 2, 'character_id' => 2, 'character_name' => 'Bravo'],
            ['killmail_id' => 3, 'character_id' => 3, 'character_name' => 'Charlie'],
            ['killmail_id' => 4, 'character_id' => 4, 'character_name' => 'Delta'],
            ['killmail_id' => 5, 'character_id' => 5, 'character_name' => 'Echo'],
            ['killmail_id' => 6, 'character_id' => 6, 'character_name' => 'Foxtrot'],
            ['killmail_id' => 7, 'character_id' => 7, 'character_name' => 'Golf'],
        ]);
        $regional = $service->regionalWithEligibility($regional, $hagilur);
        self::assertSame([4, 5, 6, 7], array_column($regional, 'character_id'));
        self::assertSame([1, 2, 3, 4], array_column($regional, 'score_position'));
        self::assertSame([1, 2, 3, null], array_column($regional, 'prize_position'));
        self::assertSame([7], array_column($service->raffleFromRegional($regional), 'character_id'));
        self::assertSame([1], array_column($service->raffleFromRegional($regional), 'score_position'));
    }

    public function testLocationsMatchConfiguredContestRegions(): void
    {
        $rules = new CompetitionRules(30002050, 10000042, 10000030);
        self::assertTrue($rules->isHagilur(30002050));
        self::assertTrue($rules->isEligibleLocation(30002050, 10000042));
        self::assertTrue($rules->isEligibleLocation(30000001, 10000030));
        self::assertFalse($rules->isEligibleLocation(30000001, 10000043));
    }

    public function testEventWindowIsStartInclusiveAndEndExclusive(): void
    {
        $rules = new CompetitionRules(30002050, 10000042, 10000030);
        $start = new \DateTimeImmutable('2026-10-01 00:00:00 UTC');
        $end = new \DateTimeImmutable('2026-11-01 00:00:00 UTC');
        self::assertTrue($rules->isWithinEvent($start, $start, $end));
        self::assertTrue($rules->isWithinEvent(new \DateTimeImmutable('2026-10-31 23:59:59 UTC'), $start, $end));
        self::assertFalse($rules->isWithinEvent($end, $start, $end));
        self::assertFalse($rules->isWithinEvent(new \DateTimeImmutable('2026-09-30 23:59:59 UTC'), $start, $end));
    }

    public function testExcludedCharactersAndKillmailsDoNotScore(): void
    {
        $board = (new LeaderboardService())->build([
            ['killmail_id' => 1, 'character_id' => 1, 'character_name' => 'Included'],
            ['killmail_id' => 2, 'character_id' => 2, 'character_name' => 'Excluded Pilot'],
            ['killmail_id' => 3, 'character_id' => 3, 'character_name' => 'Excluded Kill'],
        ], [2], [3]);
        self::assertCount(1, $board);
        self::assertSame(1, $board[0]['character_id']);
    }

    public function testRegionalWinnersAreRemovedFromRaffleAndTicketsAreDense(): void
    {
        $service = new LeaderboardService();
        $hagilur = $service->build([
            ['killmail_id' => 1, 'character_id' => 1, 'character_name' => 'Hagilur Winner'],
            ['killmail_id' => 2, 'character_id' => 2, 'character_name' => 'Hagilur Winner Two'],
            ['killmail_id' => 3, 'character_id' => 3, 'character_name' => 'Hagilur Winner Three'],
        ]);
        $regional = $service->regionalWithEligibility($service->build([
            ['killmail_id' => 1, 'character_id' => 1, 'character_name' => 'Hagilur Winner'],
            ['killmail_id' => 2, 'character_id' => 2, 'character_name' => 'Hagilur Winner Two'],
            ['killmail_id' => 3, 'character_id' => 3, 'character_name' => 'Hagilur Winner Three'],
            ['killmail_id' => 4, 'character_id' => 4, 'character_name' => 'Regional Winner'],
            ['killmail_id' => 5, 'character_id' => 5, 'character_name' => 'Regional Winner Two'],
            ['killmail_id' => 6, 'character_id' => 6, 'character_name' => 'Regional Winner Three'],
            ['killmail_id' => 7, 'character_id' => 7, 'character_name' => 'Raffle Pilot'],
            ['killmail_id' => 8, 'character_id' => 8, 'character_name' => 'Raffle Pilot Two'],
        ]), $hagilur);
        $raffle = $service->raffleFromRegional($regional);
        self::assertSame([6, 5], array_column($raffle, 'character_id'));
        self::assertSame([1, 2], array_column($raffle, 'score_position'));
        self::assertSame([1, 1], array_column($raffle, 'tickets'));
    }

    public function testStableNameOrderingBreaksTies(): void
    {
        $board = (new LeaderboardService())->build([
            ['killmail_id' => 1, 'character_id' => 1, 'character_name' => 'Zulu'],
            ['killmail_id' => 2, 'character_id' => 2, 'character_name' => 'Alpha'],
        ]);
        self::assertSame('Alpha', $board[0]['character_name']);
        self::assertSame(1, $board[0]['score_position']);
    }

    public function testPilotWhoLeavesHagilurTopThreeRegainsRegionalEligibility(): void
    {
        $service = new LeaderboardService();
        $initialHagilur = $service->build([
            ['killmail_id' => 1, 'character_id' => 1, 'character_name' => 'Pilot'],
            ['killmail_id' => 2, 'character_id' => 2, 'character_name' => 'Other'],
            ['killmail_id' => 3, 'character_id' => 3, 'character_name' => 'Other Two'],
        ]);
        $regional = $service->build([
            ['killmail_id' => 1, 'character_id' => 1, 'character_name' => 'Pilot'],
            ['killmail_id' => 4, 'character_id' => 4, 'character_name' => 'Eligible'],
        ]);
        $initial = $service->regionalWithEligibility($regional, $initialHagilur);
        self::assertNotContains(1, array_column($initial, 'character_id'));
        $newHagilur = $service->build([
            ['killmail_id' => 2, 'character_id' => 2, 'character_name' => 'Other'],
            ['killmail_id' => 3, 'character_id' => 3, 'character_name' => 'Other Two'],
            ['killmail_id' => 5, 'character_id' => 5, 'character_name' => 'New Winner'],
        ]);
        $updated = $service->regionalWithEligibility($regional, $newHagilur);
        $updatedPilot = array_values(array_filter($updated, static fn (array $pilot): bool => $pilot['character_id'] === 1))[0];
        self::assertTrue($updatedPilot['prize_eligible']);
    }
}
