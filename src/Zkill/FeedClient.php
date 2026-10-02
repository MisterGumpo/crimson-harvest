<?php

declare(strict_types=1);

namespace CrimsonHarvest\Zkill;

interface FeedClient
{
    /** @return iterable<array<string, mixed>> */
    public function messages(?string $cursor): iterable;
}
