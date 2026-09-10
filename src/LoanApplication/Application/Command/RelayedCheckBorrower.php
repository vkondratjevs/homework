<?php

declare(strict_types=1);

namespace App\LoanApplication\Application\Command;

use Symfony\Component\Uid\Uuid;

final readonly class RelayedCheckBorrower
{
    public function __construct(
        public Uuid $applicationId,
    ) {
    }
}
