<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain\Repository;

use App\LoanApplication\Domain\Entity\Application;

final readonly class ApplicationPage
{
    /**
     * @param list<Application> $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}
