<?php

declare(strict_types=1);

namespace App\LoanApplication\Application\Command;

final readonly class CreateApplication
{
    public function __construct(
        public string $personalCode,
        public string $amount,
        public int $term,
        public string $currency,
    ) {
    }
}
