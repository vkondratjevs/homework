<?php

declare(strict_types=1);

namespace App\Tests\Unit\LoanApplication\Domain\Entity;

use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Enum\ApplicationStatus;
use App\LoanApplication\Domain\Exception\ApplicationAlreadyResolvedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

final class ApplicationTest extends TestCase
{
    public function testCreateStartsInPendingStatusWithAFreshTimeOrderedId(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        self::assertSame(ApplicationStatus::Pending, $application->getStatus());
        self::assertInstanceOf(UuidV7::class, $application->getId());
    }

    public function testCreateAssignsAllFieldsFromTheArguments(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        self::assertSame('010199-12345', $application->getPersonalCode());
        self::assertSame('1000.00', $application->getAmount());
        self::assertSame(24, $application->getTerm());
        self::assertSame('EUR', $application->getCurrency());
    }

    public function testCreatedAtAndUpdatedAtStartOutEqual(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        self::assertEquals($application->getCreatedAt(), $application->getUpdatedAt());
    }

    public function testEachApplicationGetsADistinctId(): void
    {
        $first = Application::create('010199-12345', '1000.00', 24, 'EUR');
        $second = Application::create('010199-12345', '1000.00', 24, 'EUR');

        self::assertNotSame($first->getId()->toRfc4122(), $second->getId()->toRfc4122());
    }

    public function testApproveTransitionsFromPendingToApproved(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $application->approve();

        self::assertSame(ApplicationStatus::Approved, $application->getStatus());
    }

    public function testRejectTransitionsFromPendingToRejected(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $application->reject();

        self::assertSame(ApplicationStatus::Rejected, $application->getStatus());
    }

    public function testFailVerificationTransitionsFromPendingToVerificationFailed(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $application->failVerification();

        self::assertSame(ApplicationStatus::VerificationFailed, $application->getStatus());
    }

    public function testResolvingUpdatesUpdatedAt(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');
        $createdAt = $application->getUpdatedAt();

        usleep(1_000);
        $application->approve();

        self::assertGreaterThan($createdAt, $application->getUpdatedAt());
    }

    public function testApprovingAnAlreadyResolvedApplicationThrows(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');
        $application->approve();

        $this->expectException(ApplicationAlreadyResolvedException::class);

        $application->approve();
    }

    public function testRejectingAnAlreadyApprovedApplicationThrows(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');
        $application->approve();

        $this->expectException(ApplicationAlreadyResolvedException::class);

        $application->reject();
    }

    public function testFailingVerificationOnAnAlreadyResolvedApplicationThrows(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');
        $application->reject();

        $this->expectException(ApplicationAlreadyResolvedException::class);

        $application->failVerification();
    }
}
