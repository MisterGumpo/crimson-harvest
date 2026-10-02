<?php

declare(strict_types=1);

namespace CrimsonHarvest\Competition;

final class LeaderboardService
{
    public function build(array $participations, array $excludedCharacters = [], array $excludedKills = []): array
    {
        $excludedCharacters = array_fill_keys(array_map('intval', $excludedCharacters), true);
        $excludedKills = array_fill_keys(array_map('intval', $excludedKills), true);
        $scores = [];
        $counted = [];
        $affiliationTimes = [];

        foreach ($participations as $participation) {
            $characterId = (int) $participation['character_id'];
            $killmailId = (int) $participation['killmail_id'];
            if (isset($excludedCharacters[$characterId]) || isset($excludedKills[$killmailId])) {
                continue;
            }
            $participationKey = $killmailId . ':' . $characterId;
            if (isset($counted[$participationKey])) {
                continue;
            }
            $counted[$participationKey] = true;
            $scores[$characterId]['character_id'] = $characterId;
            $scores[$characterId]['character_name'] = (string) $participation['character_name'];
            $scores[$characterId]['kills'] = ($scores[$characterId]['kills'] ?? 0) + 1;
            $participationTime = $participation['killmail_time'] ?? null;
            if (!array_key_exists($characterId, $affiliationTimes) || ($participationTime !== null && ($affiliationTimes[$characterId] === null || $participationTime > $affiliationTimes[$characterId]))) {
                $affiliationTimes[$characterId] = $participationTime;
                $scores[$characterId]['corporation_id'] = isset($participation['corporation_id']) ? (int) $participation['corporation_id'] : null;
                $scores[$characterId]['alliance_id'] = isset($participation['alliance_id']) ? (int) $participation['alliance_id'] : null;
            }
        }

        usort($scores, static fn (array $left, array $right): int =>
            $right['kills'] <=> $left['kills'] ?: strcasecmp($left['character_name'], $right['character_name'])
        );
        foreach ($scores as $index => &$score) {
            $score['score_position'] = $index + 1;
        }
        unset($score);
        return $scores;
    }

    public function regionalWithEligibility(array $regional, array $hagilur): array
    {
        $hagilurWinners = array_fill_keys(array_column(array_slice($hagilur, 0, 3), 'character_id'), true);
        $prizePosition = 0;
        foreach ($regional as &$pilot) {
            $pilot['prize_eligible'] = !isset($hagilurWinners[$pilot['character_id']]);
            $pilot['ineligibility_reason'] = $pilot['prize_eligible'] ? null : 'hagilur_winner';
            $pilot['prize_position'] = $pilot['prize_eligible'] && ++$prizePosition <= 3 ? $prizePosition : null;
        }
        unset($pilot);
        return $regional;
    }
}
