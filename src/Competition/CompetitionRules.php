<?php

declare(strict_types=1);

namespace CrimsonHarvest\Competition;

use DateTimeImmutable;

final class CompetitionRules
{
    public function __construct(
        private readonly int $hagilurSystemId,
        private readonly int $metropolisRegionId,
        private readonly int $heimatarRegionId,
    ) {
    }

    public function isRegional(int $regionId): bool
    {
        return in_array($regionId, [$this->metropolisRegionId, $this->heimatarRegionId], true);
    }

    public function isHagilur(int $systemId): bool
    {
        return $systemId === $this->hagilurSystemId;
    }

    public function isEligibleLocation(int $systemId, int $regionId): bool
    {
        return $this->isRegional($regionId);
    }

    public function isWithinEvent(DateTimeImmutable $time, DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): bool
    {
        return $time >= $startsAt && $time < $endsAt;
    }
}
