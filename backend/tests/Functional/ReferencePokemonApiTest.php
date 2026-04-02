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
}
