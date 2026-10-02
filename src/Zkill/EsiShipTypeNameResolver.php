<?php

declare(strict_types=1);

namespace CrimsonHarvest\Zkill;

use PDO;
use Throwable;

final class EsiShipTypeNameResolver
{
    public function __construct(private readonly PDO $pdo, private readonly string $baseUrl = 'https://esi.evetech.net/latest')
    {
    }

    public function namesForTypes(array $typeIds): array
    {
        $typeIds = array_values(array_unique(array_filter(array_map('intval', $typeIds), static fn (int $id): bool => $id > 0)));
        if ($typeIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($typeIds), '?'));
        $cachedQuery = $this->pdo->prepare("SELECT victim_ship_type_id, victim_ship_name FROM killmails WHERE victim_ship_type_id IN ($placeholders) AND victim_ship_name IS NOT NULL");
        $cachedQuery->execute($typeIds);
        $names = [];
        foreach ($cachedQuery->fetchAll() as $row) {
            $names[(int) $row['victim_ship_type_id']] = (string) $row['victim_ship_name'];
        }
        $missingIds = array_values(array_diff($typeIds, array_keys($names)));
        if ($missingIds === []) {
            return $names;
        }

        try {
            $context = stream_context_create(['http' => [
                'method' => 'POST',
                'timeout' => 5,
                'header' => "User-Agent: CrimsonHarvest/1.0\r\nAccept: application/json\r\nContent-Type: application/json\r\n",
                'content' => json_encode($missingIds, JSON_THROW_ON_ERROR),
            ]]);
            $body = file_get_contents($this->baseUrl . '/universe/names/', false, $context);
            if ($body === false) {
                return $names;
            }
            $resolved = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $saveName = $this->pdo->prepare('UPDATE killmails SET victim_ship_name = ? WHERE victim_ship_type_id = ? AND victim_ship_name IS NULL');
            foreach ($resolved as $item) {
                if (($item['category'] ?? null) !== 'inventory_type' || !isset($item['id'], $item['name']) || !in_array((int) $item['id'], $missingIds, true)) {
                    continue;
                }
                $typeId = (int) $item['id'];
                $name = (string) $item['name'];
                $saveName->execute([$name, $typeId]);
                $names[$typeId] = $name;
            }
        } catch (Throwable) {
            return $names;
        }

        return $names;
    }
}