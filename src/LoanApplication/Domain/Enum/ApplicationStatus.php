<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain\Enum;

enum ApplicationStatus: int
{
    case Pending = 0;
    case Approved = 1;
    case Rejected = 2;
    case VerificationFailed = 3;
}
