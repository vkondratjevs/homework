<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use App\LoanApplication\Application\Command\CheckBorrower;
use App\Tests\Integration\Support\ApiTestCase;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * @phpstan-import-type ApplicationShape from ApiTestCase
 */
final class CreateApplicationDispatchesCheckBorrowerTest extends ApiTestCase
{
    public function testCreatingAnApplicationDispatchesACheckBorrowerMessageToTheOutbox(): void
    {
        $this->postJson('/applications', [
            'personalCode' => '010199-12345',
            'amount' => '1000.00',
            'term' => 24,
            'currency' => 'EUR',
        ]);
        /** @var ApplicationShape $body */
        $body = $this->decodeJsonResponse();

        /** @var array{body: mixed, headers: mixed, queue_name: string}|false $row */
        $row = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT body, headers, queue_name FROM outbox_messages ORDER BY id DESC LIMIT 1',
        );
        self::assertIsArray($row);
        self::assertSame('applications_outbox', $row['queue_name']);

        $serializer = self::getContainer()->get(SerializerInterface::class);
        self::assertInstanceOf(SerializerInterface::class, $serializer);

        self::assertIsString($row['body']);
        self::assertIsString($row['headers']);
        $headers = json_decode($row['headers'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($headers);

        $envelope = $serializer->decode(['body' => $row['body'], 'headers' => $headers]);
        $message = $envelope->getMessage();

        self::assertInstanceOf(CheckBorrower::class, $message);
        self::assertSame($body['id'], $message->applicationId->toRfc4122());
    }
}
