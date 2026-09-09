<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Persistence;

use App\LoanApplication\Domain\TransactionalSessionInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTransactionalSession implements TransactionalSessionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function transactional(callable $operation): void
    {
        $this->entityManager->wrapInTransaction($operation);
    }
}
