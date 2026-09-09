<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence;

use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Enum\ApplicationStatus;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use App\Tests\Integration\Support\ApiTestCase;

final class DoctrineApplicationRepositoryTest extends ApiTestCase
{
    public function testUpdateStatusSucceedsWhenTheRowIsStillPending(): void
    {
        $repository = $this->applicationRepository();

        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');
        $repository->save($application);

        $previousStatus = $application->getStatus();
        $application->approve();
        $updated = $repository->updateStatus($application, $previousStatus);

        self::assertTrue($updated);

        $this->entityManager->clear();
        $reloaded = $repository->findById($application->getId());
        self::assertNotNull($reloaded);
        self::assertSame(ApplicationStatus::Approved, $reloaded->getStatus());
    }

    public function testUpdateStatusFailsWhenAnotherProcessAlreadyResolvedTheRow(): void
    {
        $repository = $this->applicationRepository();

        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');
        $repository->save($application);

        // Simulate a concurrent worker winning the race: it resolves the
        // row directly in the database, bypassing this in-memory entity.
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE applications SET status = :status WHERE id = :id',
            ['status' => ApplicationStatus::Rejected->value, 'id' => $application->getId()],
            ['id' => 'uuid'],
        );

        // Our copy still thinks it's PENDING, so the guard on the entity
        // itself lets this through — only the database-level check catches it.
        $previousStatus = $application->getStatus();
        $application->approve();
        $updated = $repository->updateStatus($application, $previousStatus);

        self::assertFalse($updated);

        $this->entityManager->clear();
        $reloaded = $repository->findById($application->getId());
        self::assertNotNull($reloaded);
        self::assertSame(ApplicationStatus::Rejected, $reloaded->getStatus());
    }

    private function applicationRepository(): ApplicationRepositoryInterface
    {
        $repository = static::getContainer()->get(ApplicationRepositoryInterface::class);
        self::assertInstanceOf(ApplicationRepositoryInterface::class, $repository);

        return $repository;
    }
}
