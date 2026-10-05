<?php

declare(strict_types=1);

namespace CrimsonHarvest\Repository;

use CrimsonHarvest\Competition\LeaderboardService;
use CrimsonHarvest\Zkill\EsiShipTypeNameResolver;
use PDO;

final class DashboardRepository
{
    public function __construct(private readonly PDO $pdo, private readonly LeaderboardService $leaderboards, private readonly ?EsiShipTypeNameResolver $shipTypes = null)
    {
    }

    public function dashboard(array $event, array $locations): array
    {
        $participationQuery = $this->pdo->prepare("SELECT a.killmail_id, a.character_id, a.character_name, a.corporation_id, a.alliance_id, k.killmail_time, k.solar_system_id, k.region_id FROM killmail_attackers a INNER JOIN killmails k ON k.killmail_id = a.killmail_id WHERE k.data_source <> 'demo' AND k.killmail_time >= ? AND k.killmail_time < ? AND k.region_id IN (?, ?) AND a.alliance_id IN (99013187, 99013786) AND NOT EXISTS (SELECT 1 FROM excluded_characters ec WHERE ec.character_id = a.character_id) AND NOT EXISTS (SELECT 1 FROM excluded_killmails ek WHERE ek.killmail_id = k.killmail_id) ORDER BY a.character_name");
        $participationQuery->execute([$event['starts_at'], $event['ends_at'], $locations['metropolis'], $locations['heimatar']]);
        $participations = $participationQuery->fetchAll();
        $hagilurRows = array_values(array_filter($participations, static fn (array $row): bool => (int) $row['solar_system_id'] === (int) $locations['hagilur']));
        $hagilur = $this->leaderboards->build($hagilurRows);
        $regional = $this->leaderboards->regionalWithEligibility($this->leaderboards->build($participations), $hagilur);
        $raffle = $this->leaderboards->raffleFromRegional($regional);
        $recentQuery = $this->pdo->prepare("SELECT k.killmail_id, k.killmail_time, k.solar_system_id, COALESCE(s.name, CONCAT('System ', k.solar_system_id)) AS solar_system_name, COALESCE(MAX(CASE WHEN a.final_blow = 1 THEN a.character_name END), MIN(a.character_name)) AS attacker_name, k.victim_ship_type_id, k.victim_ship_name, k.zkill_url, COUNT(a.id) AS attacker_count FROM killmails k LEFT JOIN solar_systems s ON s.solar_system_id = k.solar_system_id LEFT JOIN killmail_attackers a ON a.killmail_id = k.killmail_id AND a.alliance_id IN (99013187, 99013786) AND NOT EXISTS (SELECT 1 FROM excluded_characters ec WHERE ec.character_id = a.character_id) WHERE k.data_source <> 'demo' AND k.killmail_time >= ? AND k.killmail_time < ? AND k.region_id IN (?, ?) AND NOT EXISTS (SELECT 1 FROM excluded_killmails ek WHERE ek.killmail_id = k.killmail_id) GROUP BY k.killmail_id HAVING attacker_count > 0 ORDER BY k.killmail_time DESC LIMIT 10");
        $recentQuery->execute([$event['starts_at'], $event['ends_at'], $locations['metropolis'], $locations['heimatar']]);
        $recent = $recentQuery->fetchAll();
        if ($this->shipTypes !== null) {
            $shipNames = $this->shipTypes->namesForTypes(array_column($recent, 'victim_ship_type_id'));
            foreach ($recent as &$kill) {
                $kill['victim_ship_name'] = $kill['victim_ship_name'] ?: ($shipNames[(int) $kill['victim_ship_type_id']] ?? null);
            }
            unset($kill);
        }
        $statsQuery = $this->pdo->prepare("SELECT COUNT(DISTINCT k.killmail_id) AS total_killmails, COUNT(a.id) AS total_participations, COUNT(DISTINCT a.character_id) AS unique_killers, COUNT(DISTINCT CASE WHEN k.solar_system_id = ? THEN k.killmail_id END) AS hagilur_killmails, MAX(k.created_at) AS latest_imported_at FROM killmails k INNER JOIN killmail_attackers a ON a.killmail_id = k.killmail_id WHERE k.data_source <> 'demo' AND k.killmail_time >= ? AND k.killmail_time < ? AND k.region_id IN (?, ?) AND a.alliance_id IN (99013187, 99013786) AND NOT EXISTS (SELECT 1 FROM excluded_characters ec WHERE ec.character_id = a.character_id) AND NOT EXISTS (SELECT 1 FROM excluded_killmails ek WHERE ek.killmail_id = k.killmail_id)");
        $statsQuery->execute([$locations['hagilur'], $event['starts_at'], $event['ends_at'], $locations['metropolis'], $locations['heimatar']]);
        $stats = $statsQuery->fetch() ?: [];
        $stats['raffle_tickets'] = array_sum(array_column($raffle, 'tickets'));
        $feed = $this->pdo->query("SELECT last_sequence, updated_at FROM feed_state WHERE provider = 'r2z2'")->fetch() ?: null;
        $feedAge = $feed ? max(0, time() - strtotime($feed['updated_at'] . ' UTC')) : null;
        return [
            'hagilur' => $hagilur,
            'hagilurPrizeWinners' => array_slice($hagilur, 0, 3),
            'regional' => $regional,
            'regionalPrizeWinners' => array_values(array_filter($regional, static fn (array $pilot): bool => $pilot['prize_position'] !== null)),
            'raffle' => $raffle,
            'recentKills' => $recent,
            'stats' => $stats,
            'ingestion' => [
                'provider' => 'R2Z2',
                'lastSequence' => $feed['last_sequence'] ?? null,
                'lastHeartbeatAt' => $feed['updated_at'] ?? null,
                'workerOnline' => $feedAge !== null && $feedAge < 45,
            ],
        ];
    }
}
