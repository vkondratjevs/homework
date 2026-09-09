<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain\Repository;

use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Enum\ApplicationStatus;
use Symfony\Component\Uid\Uuid;

interface ApplicationRepositoryInterface
{
    public function save(Application $application): void;

    /**
     * Use ``` WHERE id = :id AND status = :previousStatus``` to update the status of the application,
     * preventing race conditions when multiple processes try to update the same application simultaneously.
     * This ensures that the status is only updated if it matches the expected previous status, preventing unintended overwrites.
     */
    public function updateStatus(Application $application, ApplicationStatus $previousStatus): bool;

    public function findById(Uuid $id): ?Application;

    public function findPage(int $page, int $limit): ApplicationPage;
}
