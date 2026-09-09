<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain\Verification\Exception;

final class RetryableVerificationException extends \RuntimeException
{
    public static function fromStatusCode(int $statusCode): self
    {
        return new self(sprintf('Credit bureau responded with a retryable status code %d.', $statusCode));
    }

    public static function fromTransportFailure(\Throwable $previous): self
    {
        return new self(
            'Credit bureau request failed at the transport level (timeout or connection drop).',
            previous: $previous,
        );
    }
}
