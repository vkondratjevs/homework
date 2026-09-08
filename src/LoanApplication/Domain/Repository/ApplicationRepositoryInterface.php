<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain\Repository;

use App\LoanApplication\Domain\Entity\Application;
use Symfony\Component\Uid\Uuid;

interface ApplicationRepositoryInterface
{
    public function save(Application $application): void;

    public function find(Uuid $id): ?Application;

    /**
     * @return array{items: list<Application>, total: int}
     */
    public function findPage(int $page, int $limit): array;
}
