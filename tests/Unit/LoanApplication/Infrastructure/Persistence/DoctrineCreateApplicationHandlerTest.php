<?php

declare(strict_types=1);

namespace App\Tests\Unit\LoanApplication\Infrastructure\Persistence;

use App\LoanApplication\Application\Command\CheckBorrower;
use App\LoanApplication\Application\Command\CreateApplication;
use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Enum\ApplicationStatus;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use App\LoanApplication\Infrastructure\Persistence\DoctrineCreateApplicationHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class DoctrineCreateApplicationHandlerTest extends TestCase
{
    public function testItBuildsAPendingApplicationFromTheCommandAndSavesIt(): void
    {
        $savedApplication = null;
        $dispatchedMessage = null;

        $repository = $this->createMock(ApplicationRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('save')
            ->with(self::callback(static function (Application $application) use (&$savedApplication): bool {
                $savedApplication = $application;

                return true;
            }));

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with(self::callback(static function (CheckBorrower $message) use (&$dispatchedMessage): bool {
                $dispatchedMessage = $message;

                return true;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $operation) => $operation());

        $handler = new DoctrineCreateApplicationHandler($repository, $messageBus, $entityManager);

        $result = $handler(new CreateApplication('010199-12345', '1000.00', 24, 'EUR'));

        self::assertSame($savedApplication, $result);
        self::assertSame('010199-12345', $result->getPersonalCode());
        self::assertSame('1000.00', $result->getAmount());
        self::assertSame(24, $result->getTerm());
        self::assertSame('EUR', $result->getCurrency());
        self::assertSame(ApplicationStatus::Pending, $result->getStatus());

        self::assertNotNull($dispatchedMessage);
        self::assertSame($result->getId(), $dispatchedMessage->applicationId);
    }
}
