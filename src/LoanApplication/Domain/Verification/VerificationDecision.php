<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain\Verification;

enum VerificationDecision
{
    case Approve;
    case Reject;
}
