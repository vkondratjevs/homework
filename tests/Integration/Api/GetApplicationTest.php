<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use App\Tests\Integration\Support\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-import-type ApplicationShape from ApiTestCase
 */
final class GetApplicationTest extends ApiTestCase
{
    public function testGettingAnExistingApplicationReturnsIt(): void
    {
        $this->postJson('/applications', [
            'personalCode' => '010199-12345',
            'amount' => '1000.00',
            'term' => 24,
            'currency' => 'EUR',
        ]);
        /** @var ApplicationShape $created */
        $created = $this->decodeJsonResponse();

        $this->client->request('GET', '/applications/' . $created['id']);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame($created, $this->decodeJsonResponse());
    }

    public function testGettingAWellFormedButUnknownIdReturns404(): void
    {
        $unknownId = Uuid::v7();

        $this->client->request('GET', '/applications/' . $unknownId->toRfc4122());

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        /** @var array{error: string} $body */
        $body = $this->decodeJsonResponse();
        self::assertStringContainsString($unknownId->toRfc4122(), $body['error']);
    }

    public function testGettingAMalformedIdReturns404(): void
    {
        $this->client->request('GET', '/applications/not-a-uuid');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
