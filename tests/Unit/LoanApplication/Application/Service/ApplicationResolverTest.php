<?php

declare(strict_types=1);

namespace App\Tests\Unit\LoanApplication\Application\Service;

use App\LoanApplication\Application\Service\ApplicationResolver;
use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Enum\ApplicationStatus;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ApplicationResolverTest extends TestCase
{
    public function testApproveTransitionsTheEntityAndPersistsTheChange(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $repository = $this->createMock(ApplicationRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('updateStatus')
            ->with($application, ApplicationStatus::Pending)
            ->willReturn(true);

        $result = new ApplicationResolver($repository)->approve($application);

        self::assertTrue($result);
        self::assertSame(ApplicationStatus::Approved, $application->getStatus());
    }

    public function testRejectTransitionsTheEntityAndPersistsTheChange(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $repository = $this->createMock(ApplicationRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('updateStatus')
            ->with($application, ApplicationStatus::Pending)
            ->willReturn(true);

        $result = new ApplicationResolver($repository)->reject($application);

        self::assertTrue($result);
        self::assertSame(ApplicationStatus::Rejected, $application->getStatus());
    }

    public function testFailVerificationTransitionsTheEntityAndPersistsTheChange(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $repository = $this->createMock(ApplicationRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('updateStatus')
            ->with($application, ApplicationStatus::Pending)
            ->willReturn(true);

        $result = new ApplicationResolver($repository)->failVerification($application);

        self::assertTrue($result);
        self::assertSame(ApplicationStatus::VerificationFailed, $application->getStatus());
    }

    public function testApproveReturnsFalseWhenTheRepositoryLosesTheRace(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $repository = $this->createStub(ApplicationRepositoryInterface::class);
        $repository->method('updateStatus')->willReturn(false);

        $result = new ApplicationResolver($repository)->approve($application);

        self::assertFalse($result);
    }
}
