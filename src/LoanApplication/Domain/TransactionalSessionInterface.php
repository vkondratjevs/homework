<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain;

interface TransactionalSessionInterface
{
    /**
     * @param callable(): void $operation
     */
    public function transactional(callable $operation): void;
}
