<?php

declare(strict_types=1);

namespace App\Tests\Unit\ReferenceData\Import;

use App\ReferenceData\Import\Dto\ExtractedPokechillPokemon;
use App\ReferenceData\Import\PokechillPokemonNormalizer;
use PHPUnit\Framework\TestCase;

final class PokechillPokemonNormalizerTest extends TestCase
{
    public function testNormalizesTypesToLowercaseAndTrims(): void
    {
        $n = new PokechillPokemonNormalizer();
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
        $n = new PokechillPokemonNormalizer();
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
        $n = new PokechillPokemonNormalizer();
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
}
