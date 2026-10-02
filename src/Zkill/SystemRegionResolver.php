<?php

declare(strict_types=1);

namespace CrimsonHarvest\Zkill;

interface SystemRegionResolver
{
    public function regionForSystem(int $systemId): int;
}
