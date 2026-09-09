<?php

declare(strict_types=1);

namespace App\LoanApplication\Application\Service;

use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;

final readonly class ApplicationResolver
{
    public function __construct(
        private ApplicationRepositoryInterface $applications,
    ) {
    }

    public function approve(Application $application): bool
    {
        $previousStatus = $application->getStatus();
        $application->approve();

        return $this->applications->updateStatus($application, $previousStatus);
    }

    public function reject(Application $application): bool
    {
        $previousStatus = $application->getStatus();
        $application->reject();

        return $this->applications->updateStatus($application, $previousStatus);
    }

    public function failVerification(Application $application): bool
    {
        $previousStatus = $application->getStatus();
        $application->failVerification();

        return $this->applications->updateStatus($application, $previousStatus);
    }
}
