<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Enum\ApplicationStatus;
use App\Tests\Integration\Support\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-import-type ApplicationShape from ApiTestCase
 */
final class CreateApplicationTest extends ApiTestCase
{
    public function testCreateApplicationSuccessAsPending(): void
    {
        $this->postJson('/applications', [
            'personalCode' => '010199-12345',
            'amount' => '1000.00',
            'term' => 24,
            'currency' => 'EUR',
        ]);

        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        /** @var ApplicationShape $body */
        $body = $this->decodeJsonResponse();
        self::assertSame('010199-12345', $body['personalCode']);
        self::assertSame('1000.00', $body['amount']);
        self::assertSame(24, $body['term']);
        self::assertSame('EUR', $body['currency']);
        self::assertSame('Pending', $body['status']);

        $this->entityManager->clear();

        $application = $this->entityManager->find(Application::class, Uuid::fromString($body['id']));
        self::assertNotNull($application);
        self::assertSame(ApplicationStatus::Pending, $application->getStatus());
        self::assertSame('010199-12345', $application->getPersonalCode());
        self::assertSame('1000.00', $application->getAmount());
        self::assertSame(24, $application->getTerm());
        self::assertSame('EUR', $application->getCurrency());
    }

    public function testCreateFailsValidation(): void
    {
        $countBefore = $this->countApplications();

        $this->postJson('/applications', [
            'personalCode' => 'not-a-valid-code',
            'amount' => '1000.00',
            'term' => 24,
            'currency' => 'EUR',
        ]);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());

        /** @var array{errors: list<array{field: string, message: string}>} $body */
        $body = $this->decodeJsonResponse();
        self::assertArrayHasKey('errors', $body);

        self::assertSame($countBefore, $this->countApplications());
    }

    private function countApplications(): int
    {
        $count = $this->entityManager->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM applications')
            ->fetchOne();

        return (int) $count;
    }
}
