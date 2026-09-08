<?php

declare(strict_types=1);

namespace App\LoanApplication\Application\Handler;

use App\LoanApplication\Application\Command\CreateApplication;
use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;

final readonly class CreateApplicationHandler
{
    public function __construct(
        private ApplicationRepositoryInterface $applicationRepository,
    ) {
    }

    public function __invoke(CreateApplication $command): Application
    {
        $application = Application::create(
            $command->personalCode,
            $command->amount,
            $command->term,
            $command->currency,
        );

        $this->applicationRepository->save($application);

        return $application;
    }
}
