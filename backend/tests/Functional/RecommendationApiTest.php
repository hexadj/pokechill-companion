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

        $scores = array_map(static fn (array $r): float => $r['score'], $data['recommendations']);
        $sorted = $scores;
        rsort($sorted);
        self::assertSame($sorted, $scores);
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
}
