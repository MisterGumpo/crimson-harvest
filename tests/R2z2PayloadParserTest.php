<?php

declare(strict_types=1);

namespace CrimsonHarvest\Tests;

use CrimsonHarvest\Zkill\R2z2PayloadParser;
use CrimsonHarvest\Zkill\SystemRegionResolver;
use PHPUnit\Framework\TestCase;

final class R2z2PayloadParserTest extends TestCase
{
    public function testParsesVerifiedR2z2EnvelopeAndDropsNpcAttackers(): void
    {
        $resolver = new class implements SystemRegionResolver {
            public function regionForSystem(int $systemId): int
            {
                return 10000042;
            }
        };
        $payload = [
            'killmail_id' => 138830009,
            'esi' => [
                'killmail_time' => '2026-10-01T17:11:35Z',
                'solar_system_id' => 30002050,
                'attackers' => [
                    ['character_id' => 2122967967, 'final_blow' => true],
                    ['character_id' => 2116443854, 'final_blow' => false],
                    ['faction_id' => 500003, 'final_blow' => false],
                ],
                'victim' => ['character_id' => 895973269, 'ship_type_id' => 2161],
            ],
            'zkb' => ['totalValue' => 7491171.84],
        ];
        $killmail = (new R2z2PayloadParser($resolver))->normalize($payload, 99805593);
        self::assertSame(138830009, $killmail['killmail_id']);
        self::assertSame(10000042, $killmail['region_id']);
        self::assertCount(2, $killmail['attackers']);
        self::assertSame(99805593, $killmail['sequence']);
        self::assertSame('https://zkillboard.com/kill/138830009/', $killmail['zkill_url']);
    }

    public function testMapsDirectZkillApiEsiRecordForHistoricalRecovery(): void
    {
        $resolver = new class implements SystemRegionResolver {
            public function regionForSystem(int $systemId): int
            {
                return 10000042;
            }
        };
        $killmail = (new R2z2PayloadParser($resolver))->normalizeEsiKillmail([
            'killmail_id' => 138826271,
            'killmail_time' => '2026-10-01T12:58:20Z',
            'solar_system_id' => 30002050,
            'attackers' => [
                ['character_id' => 2123525807, 'final_blow' => true, 'damage_done' => 9193],
                ['faction_id' => 500003, 'final_blow' => false],
            ],
            'victim' => ['character_id' => 2121864283],
        ], 138826271);

        self::assertSame(138826271, $killmail['killmail_id']);
        self::assertSame('2026-10-01T12:58:20Z', $killmail['killmail_time']);
        self::assertSame(30002050, $killmail['solar_system_id']);
        self::assertSame('historical', $killmail['data_source']);
        self::assertCount(1, $killmail['attackers']);
    }
}
