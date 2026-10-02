<?php

declare(strict_types=1);

namespace CrimsonHarvest\Tests;

use CrimsonHarvest\Zkill\CharacterNameResolver;
use PHPUnit\Framework\TestCase;

final class CharacterNameResolverTest extends TestCase
{
    public function testResolverContractCanBeProvidedWithoutExternalNetworkCalls(): void
    {
        $resolver = new class implements CharacterNameResolver {
            public function nameForCharacter(int $characterId): ?string
            {
                return $characterId === 42 ? 'Verified Pilot' : null;
            }
        };
        self::assertSame('Verified Pilot', $resolver->nameForCharacter(42));
        self::assertNull($resolver->nameForCharacter(99));
    }
}
