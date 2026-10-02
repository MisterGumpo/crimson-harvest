<?php

declare(strict_types=1);

namespace CrimsonHarvest\Zkill;

use PDO;
use RuntimeException;

final class EsiCharacterNameResolver implements CharacterNameResolver
{
    public function __construct(private readonly PDO $pdo, private readonly string $baseUrl = 'https://esi.evetech.net/latest')
    {
    }

    public function nameForCharacter(int $characterId): ?string
    {
        $statement = $this->pdo->prepare('SELECT character_name FROM eve_characters WHERE character_id = ?');
        $statement->execute([$characterId]);
        $cached = $statement->fetchColumn();
        if ($cached !== false) {
            return (string) $cached;
        }
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'timeout' => 15,
            'header' => "User-Agent: CrimsonHarvest/1.0\r\nAccept: application/json\r\n",
        ]]);
        $body = file_get_contents($this->baseUrl . '/characters/' . $characterId . '/', false, $context);
        if ($body === false) {
            throw new RuntimeException('Unable to retrieve ESI character ' . $characterId . '.');
        }
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $name = isset($data['name']) && is_string($data['name']) ? trim($data['name']) : null;
        if ($name === null || $name === '') {
            return null;
        }
        $insert = $this->pdo->prepare('INSERT INTO eve_characters (character_id, character_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE character_name = VALUES(character_name)');
        $insert->execute([$characterId, $name]);
        return $name;
    }
}
