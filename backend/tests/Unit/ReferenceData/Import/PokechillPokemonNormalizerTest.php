<?php

declare(strict_types=1);

namespace App\Tests\Unit\ReferenceData\Import;

use App\ReferenceData\Import\Dto\ExtractedPokechillPokemon;
use App\ReferenceData\Import\PokechillStatRatingCalculator;
use App\ReferenceData\Import\PokechillPokemonNormalizer;
use PHPUnit\Framework\TestCase;

final class PokechillPokemonNormalizerTest extends TestCase
{
    private function normalizer(): PokechillPokemonNormalizer
    {
        return new PokechillPokemonNormalizer(new PokechillStatRatingCalculator());
    }

    public function testNormalizesTypesToLowercaseAndTrims(): void
    {
        $n = $this->normalizer();
        $extracted = [
            new ExtractedPokechillPokemon(
                sourceKey: 'fooBar',
                rename: null,
                hp: 10,
                atk: 10,
                def: 10,
                satk: 10,
                sdef: 10,
                spe: 10,
                primaryTypeCode: ' Fire ',
                secondaryTypeCode: ' WATER ',
                isHidden: false,
            ),
        ];

        $out = $n->normalize($extracted);
        self::assertCount(1, $out);
        self::assertSame('fire', $out[0]->primaryTypeCode);
        self::assertSame('water', $out[0]->secondaryTypeCode);
    }

    public function testEmptySecondaryTypeBecomesNull(): void
    {
        $n = $this->normalizer();
        $extracted = [
            new ExtractedPokechillPokemon(
                sourceKey: 'x',
                rename: 'Name',
                hp: 10,
                atk: 10,
                def: 10,
                satk: 10,
                sdef: 10,
                spe: 10,
                primaryTypeCode: 'grass',
                secondaryTypeCode: '   ',
                isHidden: false,
            ),
        ];

        $out = $n->normalize($extracted);
        self::assertNull($out[0]->secondaryTypeCode);
    }

    public function testMissingPrimaryTypeThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $n = $this->normalizer();
        $extracted = [
            new ExtractedPokechillPokemon(
                sourceKey: 'bad',
                rename: null,
                hp: 10,
                atk: 10,
                def: 10,
                satk: 10,
                sdef: 10,
                spe: 10,
                primaryTypeCode: ' ',
                secondaryTypeCode: null,
                isHidden: false,
            ),
        ];
        $n->normalize($extracted);
    }

    public function testConvertsRawBaseStatsToStarRatings(): void
    {
        $n = $this->normalizer();
        $extracted = [
            new ExtractedPokechillPokemon(
                sourceKey: 'refStat',
                rename: null,
                hp: 0,
                atk: 95,
                def: 95,
                satk: 110,
                sdef: 95,
                spe: 182,
                primaryTypeCode: 'normal',
                secondaryTypeCode: null,
                isHidden: false,
            ),
        ];

        $out = $n->normalize($extracted);
        self::assertCount(1, $out);
        self::assertSame(1, $out[0]->hp);
        self::assertSame(3, $out[0]->atk);
        self::assertSame(3, $out[0]->def);
        self::assertSame(4, $out[0]->satk);
        self::assertSame(3, $out[0]->sdef);
        self::assertSame(6, $out[0]->spe);
    }
}
