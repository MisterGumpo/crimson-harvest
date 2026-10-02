<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CrimsonHarvest\Database\Connection;
use CrimsonHarvest\Zkill\EsiCharacterNameResolver;

$pdo = Connection::fromEnvironment();
$resolver = new EsiCharacterNameResolver($pdo);
$rows = $pdo->query("SELECT DISTINCT character_id FROM killmail_attackers WHERE character_name LIKE 'Character #%'")->fetchAll(PDO::FETCH_COLUMN);
$update = $pdo->prepare('UPDATE killmail_attackers SET character_name = ? WHERE character_id = ?');
$count = 0;
foreach ($rows as $characterId) {
    $name = $resolver->nameForCharacter((int) $characterId);
    if ($name !== null) {
        $update->execute([$name, (int) $characterId]);
        $count++;
    }
}
fwrite(STDOUT, "Enriched {$count} character(s).\n");
