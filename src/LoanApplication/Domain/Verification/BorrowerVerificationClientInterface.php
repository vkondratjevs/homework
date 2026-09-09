<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain\Verification;

use App\LoanApplication\Domain\Verification\Exception\RetryableVerificationException;
use App\LoanApplication\Domain\Verification\Exception\TerminalVerificationException;
use Symfony\Component\Uid\Uuid;

interface BorrowerVerificationClientInterface
{
    /**
     * @throws RetryableVerificationException the caller should retry later
     * @throws TerminalVerificationException  retrying will not help
     */
    public function verify(Uuid $applicationId, string $personalCode, string $amount, int $term): VerificationDecision;
}
