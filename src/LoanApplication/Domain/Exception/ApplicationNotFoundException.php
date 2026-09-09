<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain\Exception;

use Symfony\Component\Uid\Uuid;

final class ApplicationNotFoundException extends \RuntimeException
{
    public static function withId(Uuid $id): self
    {
        return new self(sprintf('Application "%s" was not found.', $id->toRfc4122()));
    }
}
