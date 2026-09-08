<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Keeps every error response as JSON instead of Symfony's default HTML
 * error page — this is an API, a browser-oriented error page never applies.
 *
 * #[MapRequestPayload] wraps the real cause (ValidationFailedException for
 * constraint violations, PartialDenormalizationException for JSON/type
 * mismatches) inside a generic HttpException, so both are unwrapped here
 * to keep a single, consistent {"errors": [{field, message}]} shape.
 */
#[AsEventListener]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        foreach ($this->chain($exception) as $cause) {
            if ($cause instanceof ValidationFailedException) {
                $event->setResponse($this->unprocessable($this->fromViolations($cause->getViolations())));

                return;
            }

            if ($cause instanceof PartialDenormalizationException) {
                $event->setResponse($this->unprocessable($this->fromDenormalizationErrors($cause)));

                return;
            }
        }

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Error'],
                $exception->getStatusCode(),
            ));
        }
    }

    /** @return list<\Throwable> */
    private function chain(\Throwable $exception): array
    {
        $chain = [];
        for ($e = $exception; $e !== null; $e = $e->getPrevious()) {
            $chain[] = $e;
        }

        return $chain;
    }

    /** @return list<array{field: string, message: string}> */
    private function fromViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        return $errors;
    }

    /** @return list<array{field: string, message: string}> */
    private function fromDenormalizationErrors(PartialDenormalizationException $exception): array
    {
        $errors = [];
        foreach ($exception->getNotNormalizableValueErrors() as $error) {
            $errors[] = [
                'field' => $error->getPath() ?? '',
                'message' => $error->getMessage(),
            ];
        }

        return $errors;
    }

    /** @param list<array{field: string, message: string}> $errors */
    private function unprocessable(array $errors): JsonResponse
    {
        return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
