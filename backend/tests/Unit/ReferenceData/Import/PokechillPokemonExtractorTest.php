<?php

declare(strict_types=1);

namespace App\Tests\Unit\ReferenceData\Import;

use App\ReferenceData\Import\PokechillPokemonExtractor;
use PHPUnit\Framework\TestCase;

final class PokechillPokemonExtractorTest extends TestCase
{
    public function testHiddenEntriesAreIgnoredAndCounted(): void
    {
        $extractor = new PokechillPokemonExtractor();

        $result = $extractor->extract(<<<'JS'
pkmn.visibleMon = {
    type: ['Fire'],
    bst: { hp: 45, atk: 49, def: 49, satk: 65, sdef: 65, spe: 45 },
};
pkmn.hiddenMon = {
    hidden: true,
    type: ['Ghost'],
    bst: { hp: 30, atk: 30, def: 30, satk: 30, sdef: 30, spe: 30 },
};
JS);

        self::assertSame(2, $result['sourcePokemonCount']);
        self::assertSame(1, $result['extractedPokemonCount']);
        self::assertSame(1, $result['ignoredPokemonCount']);
        self::assertCount(1, $result['pokemons']);
        self::assertSame('visibleMon', $result['pokemons'][0]->sourceKey);
    }

    public function testZeroStatsRemainRawAtExtraction(): void
    {
        $extractor = new PokechillPokemonExtractor();

        $result = $extractor->extract(<<<'JS'
pkmn.zeroMon = {
    type: ['Normal'],
    bst: { hp: 0, atk: 10, def: 10, satk: 10, sdef: 10, spe: 0 },
};
JS);

        self::assertCount(1, $result['pokemons']);
        self::assertSame(0, $result['pokemons'][0]->hp);
        self::assertSame(0, $result['pokemons'][0]->spe);
    }
}
