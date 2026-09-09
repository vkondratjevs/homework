<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain\Exception;

use Symfony\Component\Uid\Uuid;

final class ApplicationAlreadyResolvedException extends \RuntimeException
{
    public static function withId(Uuid $id): self
    {
        return new self(sprintf('Application "%s" is no longer pending.', $id->toRfc4122()));
    }
}
