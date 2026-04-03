<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class RecommendationApiTest extends ApiWebTestCase
{
    public function testValidPayloadReturns200AndPreservesOpponentOrder(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/recommendations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'opponentSourceKeys' => ['func-opp-b', 'func-opp-a'],
                'limit' => 5,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('opponentTeam', $data);
        self::assertArrayHasKey('recommendations', $data);
        self::assertCount(2, $data['opponentTeam']);
        self::assertSame('func-opp-b', $data['opponentTeam'][0]['sourceKey']);
        self::assertSame('func-opp-a', $data['opponentTeam'][1]['sourceKey']);
        $this->assertInformativeStatsPayload($data['opponentTeam'][0]);
        $this->assertInformativeStatsPayload($data['opponentTeam'][1]);

        $scores = array_map(static fn (array $r): float => $r['score'], $data['recommendations']);
        $sorted = $scores;
        rsort($sorted);
        self::assertSame($sorted, $scores);
    }

    public function testValidPayloadIncludesUnobtainableOpponentMetadata(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/recommendations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'opponentSourceKeys' => ['func-opp-unob', 'func-opp-a'],
                'limit' => 5,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(2, $data['opponentTeam']);
        self::assertSame('func-opp-unob', $data['opponentTeam'][0]['sourceKey']);
        self::assertSame('func-opp-a', $data['opponentTeam'][1]['sourceKey']);
        $this->assertInformativeStatsPayload($data['opponentTeam'][0], false, 'unobtainable');
        $this->assertInformativeStatsPayload($data['opponentTeam'][1]);
    }

    public function testInvalidJsonReturns400(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/recommendations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{',
        );

        $data = $this->assertProblemJsonResponse(400, '/errors/bad-request', 'Bad Request');
        self::assertSame('Invalid JSON payload.', $data['detail']);
    }

    public function testUnknownOpponentKeyReturns422(): void
    {
        $missingKey = 'does-not-exist-'.bin2hex(random_bytes(4));

        $this->client->request(
            'POST',
            '/api/v1/recommendations',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'opponentSourceKeys' => [$missingKey],
            ], JSON_THROW_ON_ERROR),
        );

        $data = $this->assertProblemJsonResponse(422, '/errors/validation', 'Validation failed');
        self::assertSame('One or more opponentSourceKeys are invalid.', $data['detail']);
        self::assertSame(
            [sprintf('Unknown source keys: %s.', $missingKey)],
            $data['errors']['opponentSourceKeys'],
        );
    }

    public function testUnknownJsonKeyReturns422(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/recommendations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'opponentSourceKeys' => ['func-opp-a'],
                'extraField' => true,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testValidPayloadWithOptionalFiltersReturns200(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/recommendations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'opponentSourceKeys' => ['func-opp-a'],
                'limit' => 3,
                'includeNonObtainable' => true,
                'divisionCodes' => ['S', 'SS', 'SSS', 'D'],
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('recommendations', $data);
    }

    public function testEmptyDivisionCodesReturns422(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/recommendations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'opponentSourceKeys' => ['func-opp-a'],
                'divisionCodes' => [],
            ], JSON_THROW_ON_ERROR),
        );

        $data = $this->assertProblemJsonResponse(422, '/errors/validation', 'Validation failed');
        self::assertArrayHasKey('divisionCodes', $data['errors'] ?? []);
    }

    public function testInvalidDivisionCodeReturns422(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/recommendations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'opponentSourceKeys' => ['func-opp-a'],
                'divisionCodes' => ['X'],
            ], JSON_THROW_ON_ERROR),
        );

        $data = $this->assertProblemJsonResponse(422, '/errors/validation', 'Validation failed');
        self::assertArrayHasKey('divisionCodes', $data['errors'] ?? []);
    }

    public function testIncludeNonObtainableMustBeBooleanReturns422(): void
    {
        $this->client->request(
            'POST',
            '/api/v1/recommendations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'opponentSourceKeys' => ['func-opp-a'],
                'includeNonObtainable' => 'yes',
            ], JSON_THROW_ON_ERROR),
        );

        $data = $this->assertProblemJsonResponse(422, '/errors/validation', 'Validation failed');
        self::assertArrayHasKey('includeNonObtainable', $data['errors'] ?? []);
    }

    public function testUnobtainableCandidateExcludedByDefaultButRankedFirstWhenIncluded(): void
    {
        // Opponent must be water so fire-type candidates are favoured over water-type fixtures (e.g. func-list-a).
        $this->client->request(
            'POST',
            '/api/v1/recommendations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'opponentSourceKeys' => ['func-opp-b'],
                'limit' => 1,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotSame('func-cand-unob-best', $data['recommendations'][0]['sourceKey']);

        $this->client->request(
            'POST',
            '/api/v1/recommendations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'opponentSourceKeys' => ['func-opp-b'],
                'limit' => 1,
                'includeNonObtainable' => true,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $data2 = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('func-cand-unob-best', $data2['recommendations'][0]['sourceKey']);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function assertInformativeStatsPayload(
        array $item,
        bool $expectedIsObtainable = true,
        ?string $expectedObtainabilityCode = null,
    ): void
    {
        self::assertSame(3, $item['hp']);
        self::assertSame(4, $item['atk']);
        self::assertSame(3, $item['def']);
        self::assertSame(4, $item['satk']);
        self::assertSame(3, $item['sdef']);
        self::assertSame(3, $item['spe']);
        self::assertSame(20, $item['bstSum']);
        self::assertSame('S', $item['division']);
        self::assertSame($expectedIsObtainable, $item['isObtainable']);
        self::assertSame($expectedObtainabilityCode, $item['obtainabilityCode']);
    }
}
