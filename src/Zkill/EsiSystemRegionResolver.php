<?php

declare(strict_types=1);

namespace CrimsonHarvest\Zkill;

use PDO;
use RuntimeException;

final class EsiSystemRegionResolver implements SystemRegionResolver
{
    public function __construct(private readonly PDO $pdo, private readonly string $baseUrl = 'https://esi.evetech.net/latest')
    {
    }

    public function regionForSystem(int $systemId): int
    {
        $statement = $this->pdo->prepare('SELECT region_id FROM solar_systems WHERE solar_system_id = ?');
        $statement->execute([$systemId]);
        $cached = $statement->fetchColumn();
        if ($cached !== false) {
            return (int) $cached;
        }
        $system = $this->getJson('/universe/systems/' . $systemId . '/');
        $constellationId = filter_var($system['constellation_id'] ?? null, FILTER_VALIDATE_INT);
        if ($constellationId === false) {
            throw new RuntimeException('ESI system response has no constellation_id.');
        }
        $constellation = $this->getJson('/universe/constellations/' . $constellationId . '/');
        $regionId = filter_var($constellation['region_id'] ?? null, FILTER_VALIDATE_INT);
        if ($regionId === false) {
            throw new RuntimeException('ESI constellation response has no region_id.');
        }
        $insert = $this->pdo->prepare('INSERT INTO solar_systems (solar_system_id, region_id, name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE region_id = VALUES(region_id), name = VALUES(name)');
        $insert->execute([$systemId, $regionId, $system['name'] ?? null]);
        return (int) $regionId;
    }

    private function getJson(string $path): array
    {
        $context = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 15, 'header' => "User-Agent: CrimsonHarvest/1.0\r\nAccept: application/json\r\n"]]);
        $body = file_get_contents($this->baseUrl . $path, false, $context);
        if ($body === false) {
            throw new RuntimeException('Unable to retrieve ESI resource ' . $path . '.');
        }
        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }
}
