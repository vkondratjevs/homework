<?php

declare(strict_types=1);

namespace App\Tests\Unit\LoanApplication\Infrastructure\Http\Dto;

use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Infrastructure\Http\Dto\ApplicationResponse;
use PHPUnit\Framework\TestCase;

final class ApplicationResponseTest extends TestCase
{
    public function testFromEntityMapsAllFieldsToTheirJsonRepresentation(): void
    {
        $application = Application::create('010199-12345', '1000.00', 24, 'EUR');

        $response = ApplicationResponse::fromEntity($application);

        self::assertSame([
            'id' => $application->getId()->toRfc4122(),
            'personalCode' => '010199-12345',
            'amount' => '1000.00',
            'term' => 24,
            'currency' => 'EUR',
            'status' => 'Pending',
            'createdAt' => $application->getCreatedAt()->format(\DATE_ATOM),
        ], $response->jsonSerialize());
    }
}
