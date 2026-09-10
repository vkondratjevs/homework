<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use App\Tests\Integration\Support\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @phpstan-import-type ApplicationShape from ApiTestCase
 * @phpstan-type ApplicationListResponseShape array{
 *     items: list<ApplicationShape>,
 *     page: int,
 *     limit: int,
 *     total: int,
 * }
 */
final class ListApplicationsTest extends ApiTestCase
{
    public function testListingReturnsCreatedApplicationsNewestFirst(): void
    {
        $first = $this->createApplication('010191-12345');
        $second = $this->createApplication('010192-12345');
        $third = $this->createApplication('010193-12345');

        $this->client->request('GET', '/applications');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        /** @var ApplicationListResponseShape $body */
        $body = $this->decodeJsonResponse();

        self::assertSame(3, $body['total']);
        self::assertSame(1, $body['page']);
        self::assertSame(20, $body['limit']);
        self::assertSame(
            [$third['id'], $second['id'], $first['id']],
            array_column($body['items'], 'id'),
        );
    }

    public function testListingRespectsLimitAndPage(): void
    {
        $first = $this->createApplication('010191-12345');
        $second = $this->createApplication('010192-12345');
        $third = $this->createApplication('010193-12345');

        $this->client->request('GET', '/applications?limit=2');
        /** @var ApplicationListResponseShape $firstPage */
        $firstPage = $this->decodeJsonResponse();

        self::assertSame(3, $firstPage['total']);
        self::assertSame(2, $firstPage['limit']);
        self::assertSame([$third['id'], $second['id']], array_column($firstPage['items'], 'id'));

        $this->client->request('GET', '/applications?limit=2&page=2');
        /** @var ApplicationListResponseShape $secondPage */
        $secondPage = $this->decodeJsonResponse();

        self::assertSame(2, $secondPage['page']);
        self::assertSame([$first['id']], array_column($secondPage['items'], 'id'));
    }

    public function testListingClampsOutOfRangeLimitAndPage(): void
    {
        $this->createApplication('010199-12345');

        $this->client->request('GET', '/applications?limit=99999&page=-5');

        $body = $this->decodeJsonResponse();
        self::assertSame(1, $body['page']);
        self::assertSame(100, $body['limit']);
    }

    public function testListingAnEmptyDatabaseReturnsAnEmptyPage(): void
    {
        $this->client->request('GET', '/applications');

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $body = $this->decodeJsonResponse();
        self::assertSame([], $body['items']);
        self::assertSame(0, $body['total']);
    }

    /**
     * @return ApplicationShape
     * @throws \JsonException
     */
    private function createApplication(string $personalCode): array
    {
        $this->postJson('/applications', [
            'personalCode' => $personalCode,
            'amount' => '1000.00',
            'term' => 24,
            'currency' => 'EUR',
        ]);

        /** @var ApplicationShape */
        return $this->decodeJsonResponse();
    }
}
