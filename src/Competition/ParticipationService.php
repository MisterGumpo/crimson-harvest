<?php

declare(strict_types=1);

namespace CrimsonHarvest\Competition;

final class ParticipationService
{
    private const ELIGIBLE_ALLIANCE_IDS = [99013187, 99013786];

    public function eligibleAttackers(array $attackers): array
    {
        $unique = [];
        foreach ($attackers as $attacker) {
            $characterId = filter_var($attacker['character_id'] ?? null, FILTER_VALIDATE_INT);
            if ($characterId === false || $characterId === null || $characterId <= 0) {
                continue;
            }
            $allianceId = filter_var($attacker['alliance_id'] ?? null, FILTER_VALIDATE_INT);
            if (!in_array($allianceId, self::ELIGIBLE_ALLIANCE_IDS, true)) {
                continue;
            }
            $unique[$characterId] = $attacker + ['character_id' => $characterId];
        }
        return array_values($unique);
    }
}
