<?php

declare(strict_types=1);

namespace CrimsonHarvest\Zkill;

interface CharacterNameResolver
{
    public function nameForCharacter(int $characterId): ?string;
}
