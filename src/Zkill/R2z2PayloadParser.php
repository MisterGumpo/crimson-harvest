<?php

declare(strict_types=1);

namespace CrimsonHarvest\Zkill;

use InvalidArgumentException;

final class R2z2PayloadParser
{
    public function __construct(private readonly SystemRegionResolver $regions)
    {
    }

    public function normalize(array $package, int $sequence): array
    {
        $esi = $package['esi'] ?? null;
        if (!is_array($esi)) {
            throw new InvalidArgumentException('R2Z2 package is missing the esi object.');
        }
        $killmailId = filter_var($package['killmail_id'] ?? $esi['killmail_id'] ?? null, FILTER_VALIDATE_INT);
        if ($killmailId === false || $killmailId === null) {
            throw new InvalidArgumentException('R2Z2 package is missing a valid killmail_id.');
        }
        return $this->normalizeEsiKillmail($esi, (int) $killmailId, $sequence, $package['zkb'] ?? [], 'live');
    }

    public function normalizeEsiKillmail(array $esi, int $killmailId, int $sequence = 0, array $zkb = [], string $source = 'historical'): array
    {
        $systemId = filter_var($esi['solar_system_id'] ?? null, FILTER_VALIDATE_INT);
        $time = $esi['killmail_time'] ?? null;
        if ($killmailId < 1 || $systemId === false || $systemId === null || !is_string($time)) {
            throw new InvalidArgumentException('Killmail payload is missing required ESI fields.');
        }
        if (!is_array($zkb)) {
            $zkb = [];
        }
        $attackers = [];
        foreach ((array) ($esi['attackers'] ?? []) as $attacker) {
            if (!is_array($attacker) || !isset($attacker['character_id'])) {
                continue;
            }
            $attackers[] = $attacker;
        }
        $victim = is_array($esi['victim'] ?? null) ? $esi['victim'] : [];
        return [
            'killmail_id' => (int) $killmailId,
            'killmail_time' => $time,
            'solar_system_id' => (int) $systemId,
            'region_id' => $this->regions->regionForSystem((int) $systemId),
            'victim_character_id' => isset($victim['character_id']) ? (int) $victim['character_id'] : null,
            'victim_ship_type_id' => isset($victim['ship_type_id']) ? (int) $victim['ship_type_id'] : null,
            'total_value' => isset($zkb['totalValue']) ? (float) $zkb['totalValue'] : null,
            'zkill_url' => 'https://zkillboard.com/kill/' . (int) $killmailId . '/',
            'attackers' => $attackers,
            'sequence' => $sequence,
            'data_source' => $source,
        ];
    }
}
