<?php

declare(strict_types=1);

namespace App\Tests\Unit\LoanApplication\Application\Handler;

use App\LoanApplication\Application\Command\CreateApplication;
use App\LoanApplication\Application\Handler\CreateApplicationHandler;
use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Enum\ApplicationStatus;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CreateApplicationHandlerTest extends TestCase
{
    public function testItBuildsAPendingApplicationFromTheCommandAndSavesIt(): void
    {
        $savedApplication = null;

        $repository = $this->createMock(ApplicationRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Application $application) use (&$savedApplication): bool {
                $savedApplication = $application;

                return true;
            }));

        $handler = new CreateApplicationHandler($repository);

        $result = $handler(new CreateApplication('010199-12345', '1000.00', 24, 'EUR'));

        self::assertSame($savedApplication, $result);
        self::assertSame('010199-12345', $result->getPersonalCode());
        self::assertSame('1000.00', $result->getAmount());
        self::assertSame(24, $result->getTerm());
        self::assertSame('EUR', $result->getCurrency());
        self::assertSame(ApplicationStatus::Pending, $result->getStatus());
    }
}
