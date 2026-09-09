<?php

declare(strict_types=1);

namespace App\Tests\Unit\LoanApplication\Infrastructure\Http\Dto;

use App\LoanApplication\Infrastructure\Http\Dto\CreateApplicationRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateApplicationRequestTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    #[DataProvider('validPayloads')]
    public function testValidPayloadsProduceNoViolations(string $personalCode, string $amount, int $term, string $currency): void
    {
        $dto = new CreateApplicationRequest($personalCode, $amount, $term, $currency);

        $violations = $this->validator->validate($dto);

        self::assertCount(0, $violations, (string) $violations);
    }

    /** @return iterable<string, array{string, string, int, string}> */
    public static function validPayloads(): iterable
    {
        yield 'lower bounds' => ['010199-12345', '100.00', 10, 'EUR'];
        yield 'upper bounds' => ['010199-12345', '5000.00', 30, 'EUR'];
        yield 'single fraction digit' => ['010199-12345', '250.5', 12, 'EUR'];
        yield 'no fraction digits' => ['010199-12345', '250', 12, 'EUR'];
    }

    #[DataProvider('invalidPayloads')]
    public function testInvalidPayloadsProduceAViolationOnTheExpectedField(
        string $personalCode,
        string $amount,
        int $term,
        string $currency,
        string $expectedField,
    ): void {
        $dto = new CreateApplicationRequest($personalCode, $amount, $term, $currency);

        $violations = $this->validator->validate($dto);

        self::assertGreaterThan(0, \count($violations));

        $fields = [];
        foreach ($violations as $violation) {
            $fields[] = $violation->getPropertyPath();
        }

        self::assertContains($expectedField, $fields);
    }

    /** @return iterable<string, array{string, string, int, string, string}> */
    public static function invalidPayloads(): iterable
    {
        yield 'personalCode missing dash' => ['01019912345', '1000.00', 24, 'EUR', 'personalCode'];
        yield 'personalCode with letters' => ['01019A-12345', '1000.00', 24, 'EUR', 'personalCode'];
        yield 'personalCode too short' => ['0101-1234', '1000.00', 24, 'EUR', 'personalCode'];
        yield 'personalCode blank' => ['', '1000.00', 24, 'EUR', 'personalCode'];
        yield 'amount below minimum' => ['010199-12345', '99.99', 24, 'EUR', 'amount'];
        yield 'amount above maximum' => ['010199-12345', '5000.01', 24, 'EUR', 'amount'];
        yield 'amount with too many fraction digits' => ['010199-12345', '100.999', 24, 'EUR', 'amount'];
        yield 'amount not numeric' => ['010199-12345', 'abc', 24, 'EUR', 'amount'];
        yield 'term below minimum' => ['010199-12345', '1000.00', 9, 'EUR', 'term'];
        yield 'term above maximum' => ['010199-12345', '1000.00', 31, 'EUR', 'term'];
        yield 'currency not EUR' => ['010199-12345', '1000.00', 24, 'USD', 'currency'];
        yield 'currency blank' => ['010199-12345', '1000.00', 24, '', 'currency'];
    }
}
