<?php

declare(strict_types=1);

namespace CrimsonHarvest\Zkill;

use CrimsonHarvest\Competition\ParticipationService;
use PDO;

final class KillmailStore
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ParticipationService $participations,
        private readonly ?CharacterNameResolver $characters = null,
    )
    {
    }

    public function import(array $killmail): void
    {
        $this->pdo->beginTransaction();
        try {
            $insert = $this->pdo->prepare('INSERT IGNORE INTO killmails (killmail_id, killmail_time, solar_system_id, region_id, victim_character_id, victim_ship_type_id, zkill_url, total_value, data_source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([$killmail['killmail_id'], $killmail['killmail_time'], $killmail['solar_system_id'], $killmail['region_id'], $killmail['victim_character_id'], $killmail['victim_ship_type_id'], $killmail['zkill_url'], $killmail['total_value'], $killmail['data_source'] ?? 'live']);
            $attacker = $this->pdo->prepare('INSERT INTO killmail_attackers (killmail_id, character_id, character_name, corporation_id, alliance_id, ship_type_id, weapon_type_id, final_blow, damage_done) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE character_name = VALUES(character_name), corporation_id = VALUES(corporation_id), alliance_id = VALUES(alliance_id), ship_type_id = VALUES(ship_type_id), weapon_type_id = VALUES(weapon_type_id), final_blow = VALUES(final_blow), damage_done = VALUES(damage_done)');
            foreach ($this->participations->eligibleAttackers($killmail['attackers']) as $pilot) {
                $characterId = (int) $pilot['character_id'];
                $characterName = $pilot['character_name'] ?? null;
                if ($characterName === null && $this->characters !== null) {
                    $characterName = $this->characters->nameForCharacter($characterId);
                }
                $attacker->execute([$killmail['killmail_id'], $characterId, $characterName ?? 'Character #' . $characterId, $pilot['corporation_id'] ?? null, $pilot['alliance_id'], $pilot['ship_type_id'] ?? null, $pilot['weapon_type_id'] ?? null, (int) (bool) ($pilot['final_blow'] ?? false), $pilot['damage_done'] ?? null]);
            }
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function saveCursor(string $provider, int $sequence): void
    {
        $statement = $this->pdo->prepare('INSERT INTO feed_state (provider, last_sequence) VALUES (?, ?) ON DUPLICATE KEY UPDATE last_sequence = VALUES(last_sequence)');
        $statement->execute([$provider, (string) $sequence]);
    }

    public function heartbeat(string $provider, ?int $sequence = null): void
    {
        $statement = $this->pdo->prepare('INSERT INTO feed_state (provider, last_sequence) VALUES (?, ?) ON DUPLICATE KEY UPDATE last_sequence = COALESCE(VALUES(last_sequence), last_sequence), updated_at = CURRENT_TIMESTAMP');
        $statement->execute([$provider, $sequence === null ? null : (string) $sequence]);
    }

    public function cursor(string $provider): ?int
    {
        $statement = $this->pdo->prepare('SELECT last_sequence FROM feed_state WHERE provider = ?');
        $statement->execute([$provider]);
        $value = $statement->fetchColumn();
        return $value === false || $value === null ? null : (int) $value;
    }
}
