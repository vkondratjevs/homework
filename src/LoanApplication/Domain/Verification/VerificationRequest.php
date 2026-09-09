<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain\Verification;

use Symfony\Component\Uid\Uuid;

final readonly class VerificationRequest
{
    public function __construct(
        public Uuid $applicationId,
        public string $personalCode,
        public string $amount,
        public int $term,
    ) {
    }
}
