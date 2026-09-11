<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Http;

use App\LoanApplication\Domain\Exception\ApplicationNotFoundException;
use App\Shared\Http\ApiExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionListenerTest extends TestCase
{
    private ApiExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ApiExceptionListener();
    }

    public function testApplicationNotFoundExceptionBecomesA404(): void
    {
        $exception = ApplicationNotFoundException::withId(Uuid::v7());

        $event = $this->dispatch($exception);

        self::assertNotNull($event->getResponse());
        self::assertSame(404, $event->getResponse()->getStatusCode());
        self::assertSame(
            ['error' => $exception->getMessage()],
            json_decode((string)$event->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testValidationFailedException422(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('must not be blank', null, [], null, 'personalCode', ''),
            new ConstraintViolation('must be between 10 and 30', null, [], null, 'term', 5),
        ]);
        $validationFailure = new ValidationFailedException(null, $violations);
        // Mirrors RequestPayloadValueResolver: it never throws ValidationFailedException
        // directly, it wraps it as the previous of an UnprocessableEntityHttpException.
        $exception = new UnprocessableEntityHttpException('Validation failed.', $validationFailure);

        $event = $this->dispatch($exception);

        self::assertNotNull($event->getResponse());
        self::assertSame(422, $event->getResponse()->getStatusCode());
        self::assertSame(
            [
                'errors' => [
                    ['field' => 'personalCode', 'message' => 'must not be blank'],
                    ['field' => 'term', 'message' => 'must be between 10 and 30'],
                ],
            ],
            json_decode((string)$event->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    private function dispatch(\Throwable $exception): ExceptionEvent
    {
        $event = new ExceptionEvent(
            kernel: $this->createStub(HttpKernelInterface::class),
            request: Request::create('/'),
            requestType: HttpKernelInterface::MAIN_REQUEST,
            e: $exception,
        );

        ($this->listener)($event);

        return $event;
    }
}
