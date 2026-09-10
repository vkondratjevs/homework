<?php

declare(strict_types=1);

namespace App\Tests\Unit\LoanApplication\Infrastructure\Messaging;

use App\LoanApplication\Application\Command\RelayedCheckBorrower;
use App\LoanApplication\Application\Service\ApplicationResolver;
use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Enum\ApplicationStatus;
use App\LoanApplication\Domain\Exception\ApplicationNotFoundException;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use App\LoanApplication\Domain\Verification\BorrowerVerificationClientInterface;
use App\LoanApplication\Domain\Verification\Exception\RetryableVerificationException;
use App\LoanApplication\Domain\Verification\Exception\TerminalVerificationException;
use App\LoanApplication\Domain\Verification\VerificationDecision;
use App\LoanApplication\Infrastructure\Messaging\CheckBorrowerVerificationHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class CheckBorrowerVerificationHandlerTest extends TestCase
{
    public function testApprovesTheApplicationWhenTheVendorApproves(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $repository = $this->createStub(ApplicationRepositoryInterface::class);
        $repository->method('findById')->willReturn($application);
        $repository->method('updateStatus')->willReturn(true);

        $verificationClient = $this->createStub(BorrowerVerificationClientInterface::class);
        $verificationClient->method('verify')->willReturn(VerificationDecision::Approve);

        $handler = new CheckBorrowerVerificationHandler(
            $repository,
            $verificationClient,
            new ApplicationResolver($repository),
            $this->createStub(LoggerInterface::class),
        );

        $handler(new RelayedCheckBorrower($application->getId()));

        self::assertSame(ApplicationStatus::Approved, $application->getStatus());
    }

    public function testRejectsTheApplicationWhenTheVendorRejects(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $repository = $this->createStub(ApplicationRepositoryInterface::class);
        $repository->method('findById')->willReturn($application);
        $repository->method('updateStatus')->willReturn(true);

        $verificationClient = $this->createStub(BorrowerVerificationClientInterface::class);
        $verificationClient->method('verify')->willReturn(VerificationDecision::Reject);

        $handler = new CheckBorrowerVerificationHandler(
            $repository,
            $verificationClient,
            new ApplicationResolver($repository),
            $this->createStub(LoggerInterface::class),
        );

        $handler(new RelayedCheckBorrower($application->getId()));

        self::assertSame(ApplicationStatus::Rejected, $application->getStatus());
    }

    public function testMarksVerificationFailedWhenTheVendorTerminallyFails(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $repository = $this->createStub(ApplicationRepositoryInterface::class);
        $repository->method('findById')->willReturn($application);
        $repository->method('updateStatus')->willReturn(true);

        $verificationClient = $this->createStub(BorrowerVerificationClientInterface::class);
        $verificationClient->method('verify')->willThrowException(TerminalVerificationException::fromStatusCode(400));

        $handler = new CheckBorrowerVerificationHandler(
            $repository,
            $verificationClient,
            new ApplicationResolver($repository),
            $this->createStub(LoggerInterface::class),
        );

        $handler(new RelayedCheckBorrower($application->getId()));

        self::assertSame(ApplicationStatus::VerificationFailed, $application->getStatus());
    }

    public function testLetsARetryableVendorFailurePropagate(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $repository = $this->createStub(ApplicationRepositoryInterface::class);
        $repository->method('findById')->willReturn($application);

        $verificationClient = $this->createStub(BorrowerVerificationClientInterface::class);
        $verificationClient->method('verify')->willThrowException(RetryableVerificationException::fromStatusCode(503));

        $handler = new CheckBorrowerVerificationHandler(
            $repository,
            $verificationClient,
            new ApplicationResolver($repository),
            $this->createStub(LoggerInterface::class),
        );

        $this->expectException(RetryableVerificationException::class);

        $handler(new RelayedCheckBorrower($application->getId()));
    }

    public function testThrowsWhenTheApplicationCannotBeFound(): void
    {
        $applicationId = Uuid::v7();

        $repository = $this->createStub(ApplicationRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);

        $handler = new CheckBorrowerVerificationHandler(
            $repository,
            $this->createStub(BorrowerVerificationClientInterface::class),
            new ApplicationResolver($repository),
            $this->createStub(LoggerInterface::class),
        );

        $this->expectException(ApplicationNotFoundException::class);

        $handler(new RelayedCheckBorrower($applicationId));
    }

    public function testLogsInsteadOfThrowingWhenTheStatusUpdateLosesTheRace(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $repository = $this->createStub(ApplicationRepositoryInterface::class);
        $repository->method('findById')->willReturn($application);
        $repository->method('updateStatus')->willReturn(false);

        $verificationClient = $this->createStub(BorrowerVerificationClientInterface::class);
        $verificationClient->method('verify')->willReturn(VerificationDecision::Approve);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');

        $handler = new CheckBorrowerVerificationHandler(
            $repository,
            $verificationClient,
            new ApplicationResolver($repository),
            $logger,
        );

        $handler(new RelayedCheckBorrower($application->getId()));
    }

    public function testSkipsTheVendorCallWhenTheApplicationWasAlreadyResolved(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');
        $application->approve();

        $repository = $this->createStub(ApplicationRepositoryInterface::class);
        $repository->method('findById')->willReturn($application);

        $verificationClient = $this->createMock(BorrowerVerificationClientInterface::class);
        $verificationClient->expects($this->never())->method('verify');

        $handler = new CheckBorrowerVerificationHandler(
            $repository,
            $verificationClient,
            new ApplicationResolver($repository),
            $this->createStub(LoggerInterface::class),
        );

        $handler(new RelayedCheckBorrower($application->getId()));

        self::assertSame(ApplicationStatus::Approved, $application->getStatus());
    }
}
