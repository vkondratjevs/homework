<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain\Verification\Exception;

final class TerminalVerificationException extends \RuntimeException
{
    public static function fromStatusCode(int $statusCode): self
    {
        return new self(sprintf('Credit bureau responded with a non-retryable status code %d.', $statusCode));
    }

    public static function unexpectedResponseShape(): self
    {
        return new self('Credit bureau returned a 200 response with an unexpected body shape.');
    }
}
