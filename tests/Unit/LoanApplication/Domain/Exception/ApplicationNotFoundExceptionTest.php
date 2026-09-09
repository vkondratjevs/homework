<?php

declare(strict_types=1);

namespace App\Tests\Unit\LoanApplication\Domain\Exception;

use App\LoanApplication\Domain\Exception\ApplicationNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ApplicationNotFoundExceptionTest extends TestCase
{
    public function testWithIdIncludesTheIdInTheMessage(): void
    {
        $id = Uuid::v7();

        $exception = ApplicationNotFoundException::withId($id);

        self::assertStringContainsString($id->toRfc4122(), $exception->getMessage());
    }
}
