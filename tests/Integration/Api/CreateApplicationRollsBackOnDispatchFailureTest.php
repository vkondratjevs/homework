<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use App\LoanApplication\Application\Command\CreateApplication;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use App\LoanApplication\Infrastructure\Persistence\DoctrineCreateApplicationHandler;
use App\Tests\Integration\Support\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CreateApplicationRollsBackOnDispatchFailureTest extends ApiTestCase
{
    public function testAFailedDispatchRollsBackTheApplicationSaveToo(): void
    {
        /** @var ApplicationRepositoryInterface $repository */
        $repository = self::getContainer()->get(ApplicationRepositoryInterface::class);
        self::assertInstanceOf(ApplicationRepositoryInterface::class, $repository);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $failingBus = new class implements MessageBusInterface {
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                throw new \RuntimeException('Simulated dispatch failure.');
            }
        };

        $handler = new DoctrineCreateApplicationHandler($repository, $failingBus, $entityManager);

        $countBefore = $this->countApplications();

        try {
            $handler(new CreateApplication('010199-12345', '1000.00', 24, 'EUR'));
            self::fail('Expected the simulated dispatch failure to propagate.');
        } catch (\RuntimeException $e) {
            self::assertSame('Simulated dispatch failure.', $e->getMessage());
        }

        self::assertSame($countBefore, $this->countApplications());
    }

    private function countApplications(): int
    {
        return (int) $this->entityManager->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM applications')
            ->fetchOne();
    }
}
