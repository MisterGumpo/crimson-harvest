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
        $regional = array_values(array_filter(
            $regional,
            static fn (array $pilot): bool => !isset($hagilurWinners[$pilot['character_id']])
        ));
        foreach ($regional as $index => &$pilot) {
            $pilot['score_position'] = $index + 1;
            $pilot['prize_eligible'] = true;
            $pilot['ineligibility_reason'] = null;
            $pilot['prize_position'] = $index < 3 ? $index + 1 : null;
        }
        unset($pilot);
        return $regional;
    }

    public function raffleFromRegional(array $regional): array
    {
        $raffle = array_values(array_filter(
            $regional,
            static fn (array $pilot): bool => $pilot['prize_position'] === null
        ));
        foreach ($raffle as $index => &$pilot) {
            $pilot['score_position'] = $index + 1;
            $pilot['tickets'] = $pilot['kills'];
        }
        unset($pilot);
        return $raffle;
    }
}
