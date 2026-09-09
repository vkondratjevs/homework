<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use App\LoanApplication\Application\Command\CreateApplication;
use App\LoanApplication\Application\Handler\CreateApplicationHandler;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use App\LoanApplication\Domain\TransactionalSessionInterface;
use App\Tests\Integration\Support\ApiTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CreateApplicationRollsBackOnDispatchFailureTest extends ApiTestCase
{
    public function testAFailedDispatchRollsBackTheApplicationSaveToo(): void
    {
        /** @var ApplicationRepositoryInterface $repository */
        $repository = self::getContainer()->get(ApplicationRepositoryInterface::class);
        self::assertInstanceOf(ApplicationRepositoryInterface::class, $repository);

        /** @var TransactionalSessionInterface $transactionalSession */
        $transactionalSession = self::getContainer()->get(TransactionalSessionInterface::class);
        self::assertInstanceOf(TransactionalSessionInterface::class, $transactionalSession);

        $failingBus = new class implements MessageBusInterface {
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                throw new \RuntimeException('Simulated dispatch failure.');
            }
        };

        $handler = new CreateApplicationHandler($repository, $transactionalSession, $failingBus);

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
        $count = $this->entityManager->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM applications')
            ->fetchOne();

        return (int) $count;
    }
}
