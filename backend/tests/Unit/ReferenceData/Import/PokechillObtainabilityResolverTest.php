<?php

declare(strict_types=1);

namespace App\Tests\Unit\ReferenceData\Import;

use App\ReferenceData\Import\Dto\PokechillAreaObtainabilityRow;
use App\ReferenceData\Import\PokechillEvolutionGraphBuilder;
use App\ReferenceData\Import\PokechillObtainabilityResolver;
use PHPUnit\Framework\TestCase;

final class PokechillObtainabilityResolverTest extends TestCase
{
    public function testMartTag(): void
    {
        $resolver = new PokechillObtainabilityResolver(new PokechillEvolutionGraphBuilder());
        $allKeys = ['martMon'];
        $pools = ['common' => [], 'uncommon' => [], 'rare' => [], 'frontierExclusive' => []];
        $adj = ['martMon' => []];

        $out = $resolver->resolve($allKeys, [], $pools, ['martMon'], $adj);

        self::assertSame('mart', $out['martMon']['code']);
        self::assertTrue($out['martMon']['isObtainable']);
    }

    public function testWildTagFromWildAreaSpawn(): void
    {
        $resolver = new PokechillObtainabilityResolver(new PokechillEvolutionGraphBuilder());

        $out = $resolver->resolve(
            ['wildMon'],
            [$this->areaRow(type: 'wild', wildSpawnKeys: ['wildMon'])],
            ['common' => [], 'uncommon' => [], 'rare' => [], 'frontierExclusive' => []],
            [],
            ['wildMon' => []],
        );

        self::assertSame('wild', $out['wildMon']['code']);
        self::assertTrue($out['wildMon']['isObtainable']);
    }

    public function testEventTagFromEventAreaSpawn(): void
    {
        $resolver = new PokechillObtainabilityResolver(new PokechillEvolutionGraphBuilder());

        $out = $resolver->resolve(
            ['eventMon'],
            [$this->areaRow(type: 'event', eventSpawnKeys: ['eventMon'])],
            ['common' => [], 'uncommon' => [], 'rare' => [], 'frontierExclusive' => []],
            [],
            ['eventMon' => []],
        );

        self::assertSame('event', $out['eventMon']['code']);
        self::assertTrue($out['eventMon']['isObtainable']);
    }

    public function testFrontierTagFromExclusivePool(): void
    {
        $resolver = new PokechillObtainabilityResolver(new PokechillEvolutionGraphBuilder());

        $out = $resolver->resolve(
            ['frontierMon'],
            [],
            ['common' => [], 'uncommon' => [], 'rare' => [], 'frontierExclusive' => ['frontierMon']],
            [],
            ['frontierMon' => []],
        );

        self::assertSame('frontier', $out['frontierMon']['code']);
        self::assertTrue($out['frontierMon']['isObtainable']);
    }

    public function testUnobtainableWhenFamilyHasNoDirectRoute(): void
    {
        $resolver = new PokechillObtainabilityResolver(new PokechillEvolutionGraphBuilder());
        $pkmnJs = <<<'JS'
pkmn.bulbasaur = { 
 type: ["grass","poison"], 
 bst: { hp: 45, atk: 49, def: 49, satk: 65, sdef: 65, spe: 45 },
 evolve: function() { return { 1: { pkmn: pkmn.ivysaur, level: 1 } } },
}
pkmn.ivysaur = { 
 type: ["grass","poison"], 
 bst: { hp: 60, atk: 62, def: 63, satk: 80, sdef: 80, spe: 60 },
}
JS;
        $adj = (new PokechillEvolutionGraphBuilder())->buildUndirectedAdjacency($pkmnJs);
        $areas = [];
        $pools = ['common' => [], 'uncommon' => [], 'rare' => [], 'frontierExclusive' => []];
        $out = $resolver->resolve(['bulbasaur', 'ivysaur'], $areas, $pools, [], $adj);

        self::assertSame('unobtainable', $out['bulbasaur']['code']);
        self::assertSame('unobtainable', $out['ivysaur']['code']);
        self::assertFalse($out['bulbasaur']['isObtainable']);
    }

    public function testEvolutionFamilyWithDirectRouteDoesNotBecomeUnobtainable(): void
    {
        $resolver = new PokechillObtainabilityResolver(new PokechillEvolutionGraphBuilder());
        $pkmnJs = <<<'JS'
pkmn.bulbasaur = {
 type: ["grass","poison"],
 bst: { hp: 45, atk: 49, def: 49, satk: 65, sdef: 65, spe: 45 },
 evolve: function() { return { 1: { pkmn: pkmn.ivysaur, level: 1 } } },
}
pkmn.ivysaur = {
 type: ["grass","poison"],
 bst: { hp: 60, atk: 62, def: 63, satk: 80, sdef: 80, spe: 60 },
}
JS;
        $adj = (new PokechillEvolutionGraphBuilder())->buildUndirectedAdjacency($pkmnJs);
        $areas = [$this->areaRow(type: 'wild', wildSpawnKeys: ['bulbasaur'])];
        $pools = ['common' => [], 'uncommon' => [], 'rare' => [], 'frontierExclusive' => []];
        $out = $resolver->resolve(['bulbasaur', 'ivysaur'], $areas, $pools, [], $adj);

        self::assertSame('wild', $out['bulbasaur']['code']);
        self::assertNull($out['ivysaur']['code']);
        self::assertTrue($out['ivysaur']['isObtainable']);
    }

    public function testArceusSpecialCase(): void
    {
        $resolver = new PokechillObtainabilityResolver(new PokechillEvolutionGraphBuilder());
        $pkmnJs = <<<'JS'
pkmn.arceus = { type: ["normal"], bst: { hp: 120, atk: 120, def: 120, satk: 120, sdef: 120, spe: 120 } }
JS;
        $adj = (new PokechillEvolutionGraphBuilder())->buildUndirectedAdjacency($pkmnJs);
        $pools = ['common' => [], 'uncommon' => [], 'rare' => [], 'frontierExclusive' => []];
        $out = $resolver->resolve(['arceus'], [], $pools, [], $adj);

        self::assertSame('arceus', $out['arceus']['code']);
        self::assertTrue($out['arceus']['isObtainable']);
    }

    /**
     * @param list<string> $wildSpawnKeys
     * @param list<string> $eventSpawnKeys
     * @param list<string> $rewardKeys
     */
    private function areaRow(
        ?string $type,
        bool $uncatchable = false,
        bool $encounter = false,
        array $wildSpawnKeys = [],
        array $eventSpawnKeys = [],
        ?string $encounterSlot1Key = null,
        array $rewardKeys = [],
    ): PokechillAreaObtainabilityRow {
        return new PokechillAreaObtainabilityRow(
            name: 'test-area',
            type: $type,
            uncatchable: $uncatchable,
            encounter: $encounter,
            wildSpawnKeys: $wildSpawnKeys,
            eventSpawnKeys: $eventSpawnKeys,
            encounterSlot1Key: $encounterSlot1Key,
            rewardKeys: $rewardKeys,
        );
    }
}
