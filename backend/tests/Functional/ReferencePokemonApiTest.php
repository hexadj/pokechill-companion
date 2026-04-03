<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class ReferencePokemonApiTest extends ApiWebTestCase
{
    public function testListReturnsItemsSortedByName(): void
    {
        $this->client->request('GET', '/api/v1/reference/pokemon', [
            'search' => 'Func',
            'limit' => 20,
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('items', $data);
        $names = array_map(static fn (array $row): string => $row['name'], $data['items']);
        $sorted = $names;
        sort($sorted);
        self::assertSame($sorted, $names);

        foreach ($data['items'] as $item) {
            $this->assertInformativeStatsPayload($item);
        }
    }

    public function testSearchFiltersByName(): void
    {
        $this->client->request('GET', '/api/v1/reference/pokemon', [
            'search' => 'Zzz',
            'limit' => 10,
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($data['items']);
        foreach ($data['items'] as $item) {
            self::assertStringContainsStringIgnoringCase('zzz', $item['name']);
        }
    }

    public function testListRespectsValidLimit(): void
    {
        $this->client->request('GET', '/api/v1/reference/pokemon', [
            'search' => 'Func',
            'limit' => 2,
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(2, $data['items']);
    }

    public function testListReturnsOnlyActivePokemon(): void
    {
        $this->client->request('GET', '/api/v1/reference/pokemon', [
            'search' => 'Hidden',
            'limit' => 20,
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([], $data['items']);
    }

    public function testInvalidLimitReturns422(): void
    {
        $this->client->request('GET', '/api/v1/reference/pokemon?limit=0');

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function assertInformativeStatsPayload(array $item): void
    {
        self::assertSame(3, $item['hp']);
        self::assertSame(4, $item['atk']);
        self::assertSame(3, $item['def']);
        self::assertSame(4, $item['satk']);
        self::assertSame(3, $item['sdef']);
        self::assertSame(3, $item['spe']);
        self::assertSame(20, $item['bstSum']);
        self::assertSame('S', $item['division']);
    }
}
