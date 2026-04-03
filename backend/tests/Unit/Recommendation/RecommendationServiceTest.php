<?php

declare(strict_types=1);

namespace App\Tests\Unit\Recommendation;

use App\Entity\Pokemon;
use App\Recommendation\Dto\RecommendationQuery;
use App\ReferenceData\Import\PokechillDivisionCalculator;
use App\Recommendation\Service\MatchupScorer;
use App\Recommendation\Service\RecommendationService;
use App\Repository\PokemonRepository;
use App\Repository\TypeEffectivenessRepository;
use App\ReferenceData\Dto\TypeEffectivenessMatrix;
use App\ReferenceData\Seeder\TypeEffectivenessMatrixBuilder;
use App\Tests\Support\PokemonTestFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class RecommendationServiceTest extends TestCase
{
    /**
     * @param array<string, Pokemon> $activeByKey
     * @param Pokemon[]              $allActive
     */
    private function makePokemonRepository(array $activeByKey, array $allActive): PokemonRepository
    {
        $em = $this->createStub(EntityManagerInterface::class);

        return new class($em, $activeByKey, $allActive) extends PokemonRepository {
            /**
             * @param array<string, Pokemon> $activeByKey
             * @param Pokemon[]              $allActive
             */
            public function __construct(
                EntityManagerInterface $em,
                private readonly array $activeByKey,
                private readonly array $allActive,
            ) {
                parent::__construct($em);
            }

            public function findActiveBySourceKeys(array $sourceKeys): array
            {
                $sourceKeys = array_values(array_unique(array_map('strval', $sourceKeys)));
                $out = [];
                foreach ($sourceKeys as $k) {
                    if (isset($this->activeByKey[$k])) {
                        $out[$k] = $this->activeByKey[$k];
                    }
                }

                return $out;
            }

            /**
             * @return Pokemon[]
             */
            public function findAllActive(): array
            {
                return $this->allActive;
            }
        };
    }

    private function makeMatrixRepository(): TypeEffectivenessRepository
    {
        $em = $this->createStub(EntityManagerInterface::class);

        return new class($em) extends TypeEffectivenessRepository {
            public function __construct(EntityManagerInterface $em)
            {
                parent::__construct($em);
            }

            public function getMatrix(): TypeEffectivenessMatrix
            {
                return TypeEffectivenessMatrixBuilder::build();
            }
        };
    }

    private function service(PokemonRepository $pokemonRepo): RecommendationService
    {
        return new RecommendationService(
            $pokemonRepo,
            $this->makeMatrixRepository(),
            new MatchupScorer(),
            new PokechillDivisionCalculator(),
        );
    }

    public function testRanksStrongerCandidateFirstWhenScoresDiffer(): void
    {
        $fire = PokemonTestFactory::type('fire');
        $water = PokemonTestFactory::type('water');

        $opp = PokemonTestFactory::pokemon('opp', 'Opp', $water, null, 50, 100, 50, 100);
        $weak = PokemonTestFactory::pokemon('weak', 'Weak', $fire, null, 10, 50, 10, 50);
        $strong = PokemonTestFactory::pokemon('strong', 'Strong', $fire, null, 200, 50, 200, 50);

        $repo = $this->makePokemonRepository(['opp' => $opp], [$weak, $strong]);

        $result = $this->service($repo)->recommend(new RecommendationQuery(['opp'], 10));

        self::assertSame('strong', $result->recommendations[0]->sourceKey);
        self::assertSame('weak', $result->recommendations[1]->sourceKey);
    }

    public function testRespectsLimit(): void
    {
        $fire = PokemonTestFactory::type('fire');
        $water = PokemonTestFactory::type('water');

        $opp = PokemonTestFactory::pokemon('opp', 'Opp', $water, null, 50, 100, 50, 100);
        $a = PokemonTestFactory::pokemon('a', 'A', $fire, null, 10, 50, 10, 50);
        $b = PokemonTestFactory::pokemon('b', 'B', $fire, null, 20, 50, 20, 50);

        $repo = $this->makePokemonRepository(['opp' => $opp], [$a, $b]);

        $result = $this->service($repo)->recommend(new RecommendationQuery(['opp'], 1));

        self::assertCount(1, $result->recommendations);
        self::assertSame('b', $result->recommendations[0]->sourceKey);
    }

    public function testPreservesOpponentOrderIncludingDuplicates(): void
    {
        $fire = PokemonTestFactory::type('fire');
        $water = PokemonTestFactory::type('water');
        $grass = PokemonTestFactory::type('grass');

        $first = PokemonTestFactory::pokemon('first', 'First', $water, null, 50, 50, 50, 50);
        $second = PokemonTestFactory::pokemon('second', 'Second', $grass, null, 50, 50, 50, 50);

        $c = PokemonTestFactory::pokemon('c', 'Cand', $fire, null, 80, 50, 80, 50);

        $repo = $this->makePokemonRepository(
            [
                'first' => $first,
                'second' => $second,
            ],
            [$c],
        );

        $result = $this->service($repo)->recommend(new RecommendationQuery(['first', 'second', 'first'], 5));

        self::assertCount(3, $result->opponentTeam);
        self::assertSame('first', $result->opponentTeam[0]->sourceKey);
        self::assertSame('second', $result->opponentTeam[1]->sourceKey);
        self::assertSame('first', $result->opponentTeam[2]->sourceKey);
    }

    public function testTieBreakerUsesMaxOffenseThenSourceKey(): void
    {
        $fire = PokemonTestFactory::type('fire');
        $water = PokemonTestFactory::type('water');

        $opp = PokemonTestFactory::pokemon('opp', 'Opp', $water, null, 100, 100, 100, 100);
        $lowerKeyHigherOffense = PokemonTestFactory::pokemon('aaa', 'A', $fire, null, 120, 100, 120, 100);
        $higherKeyLowerOffense = PokemonTestFactory::pokemon('zzz', 'Z', $fire, null, 100, 100, 100, 100);

        $repo = $this->makePokemonRepository(
            ['opp' => $opp],
            [$higherKeyLowerOffense, $lowerKeyHigherOffense],
        );

        $result = $this->service($repo)->recommend(new RecommendationQuery(['opp'], 10));

        self::assertSame('aaa', $result->recommendations[0]->sourceKey);

        $identicalA = PokemonTestFactory::pokemon('m', 'M', $fire, null, 100, 100, 100, 100);
        $identicalB = PokemonTestFactory::pokemon('n', 'N', $fire, null, 100, 100, 100, 100);

        $repo2 = $this->makePokemonRepository(
            ['opp' => $opp],
            [$identicalB, $identicalA],
        );

        $result2 = $this->service($repo2)->recommend(new RecommendationQuery(['opp'], 10));

        self::assertSame('m', $result2->recommendations[0]->sourceKey);
        self::assertSame('n', $result2->recommendations[1]->sourceKey);
    }
}
