<?php

declare(strict_types=1);

namespace App\Tests\Unit\Recommendation;

use App\Recommendation\Service\MatchupScorer;
use App\ReferenceData\Seeder\TypeEffectivenessMatrixBuilder;
use App\Tests\Support\PokemonTestFactory;
use PHPUnit\Framework\TestCase;

final class MatchupScorerTest extends TestCase
{
    private MatchupScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new MatchupScorer();
    }

    public function testMonoTypeAgainstMonoTypeUsesMatrixAndStats(): void
    {
        $matrix = TypeEffectivenessMatrixBuilder::build();
        $fire = PokemonTestFactory::type('fire');
        $water = PokemonTestFactory::type('water');

        $candidate = PokemonTestFactory::pokemon('c', 'Cand', $fire, null, 90, 50, 30, 50);
        $opponent = PokemonTestFactory::pokemon('o', 'Opp', $water, null, 10, 100, 10, 100);

        $mv = $this->scorer->scoreMatchup($candidate, $opponent, $matrix);

        self::assertSame('o', $mv->opponentSourceKey);
        $expectedM = $matrix->getMultiplierX100('fire', 'water');
        self::assertSame($expectedM, $mv->typeMultiplierX100);
        $mult = $expectedM / 100.0;
        self::assertEqualsWithDelta($mult * (90 / 100), $mv->physicalScore, 1e-6);
        self::assertEqualsWithDelta($mult * (30 / 100), $mv->specialScore, 1e-6);
        self::assertSame('physical', $mv->bestAttackCategory);
    }

    public function testImmunityYieldsZeroMultiplierAndScores(): void
    {
        $matrix = TypeEffectivenessMatrixBuilder::build();
        $normal = PokemonTestFactory::type('normal');
        $ghost = PokemonTestFactory::type('ghost');

        $candidate = PokemonTestFactory::pokemon('c', 'Cand', $normal, null, 100, 50, 100, 50);
        $opponent = PokemonTestFactory::pokemon('o', 'Opp', $ghost, null, 50, 50, 50, 50);

        $mv = $this->scorer->scoreMatchup($candidate, $opponent, $matrix);

        self::assertSame(0, $mv->typeMultiplierX100);
        self::assertEqualsWithDelta(0.0, $mv->selectedScore, 1e-9);
    }

    public function testDoubleTypeOpponentCombinesMultipliers(): void
    {
        $matrix = TypeEffectivenessMatrixBuilder::build();
        $fire = PokemonTestFactory::type('fire');
        $rock = PokemonTestFactory::type('rock');
        $water = PokemonTestFactory::type('water');

        $candidate = PokemonTestFactory::pokemon('c', 'Cand', $fire, null, 100, 100, 50, 100);
        $opponent = PokemonTestFactory::pokemon('o', 'Opp', $rock, $water, 10, 100, 10, 100);

        $mv = $this->scorer->scoreMatchup($candidate, $opponent, $matrix);

        $m1 = $matrix->getMultiplierX100('fire', 'rock');
        $m2 = $matrix->getMultiplierX100('fire', 'water');
        self::assertSame(intdiv($m1 * $m2, 100), $mv->typeMultiplierX100);
        self::assertSame(25, $mv->typeMultiplierX100);
    }

    public function testDoubleTypeCandidatePicksBestOffensiveType(): void
    {
        $matrix = TypeEffectivenessMatrixBuilder::build();
        $water = PokemonTestFactory::type('water');
        $grass = PokemonTestFactory::type('grass');
        $rock = PokemonTestFactory::type('rock');

        $candidate = PokemonTestFactory::pokemon('c', 'Cand', $water, $grass, 80, 50, 80, 50);
        $opponent = PokemonTestFactory::pokemon('o', 'Opp', $rock, null, 10, 100, 10, 100);

        $mv = $this->scorer->scoreMatchup($candidate, $opponent, $matrix);

        self::assertContains($mv->bestAttackTypeCode, ['water', 'grass']);
        $waterScore = $matrix->getMultiplierX100('water', 'rock') / 100.0 * (80 / 100);
        $grassScore = $matrix->getMultiplierX100('grass', 'rock') / 100.0 * (80 / 100);
        self::assertEqualsWithDelta(max($waterScore, $grassScore), $mv->selectedScore, 1e-9);
    }

    public function testPrefersPhysicalOnTieWithinEpsilon(): void
    {
        $matrix = TypeEffectivenessMatrixBuilder::build();
        $fire = PokemonTestFactory::type('fire');
        $water = PokemonTestFactory::type('water');

        $candidate = PokemonTestFactory::pokemon('c', 'Cand', $fire, null, 100, 100, 100, 100);
        $opponent = PokemonTestFactory::pokemon('o', 'Opp', $water, null, 100, 100, 100, 100);

        $mv = $this->scorer->scoreMatchup($candidate, $opponent, $matrix);

        self::assertSame('physical', $mv->bestAttackCategory);
    }
}
