<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\LoanApplication\Domain\Exception\ApplicationNotFoundException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ApplicationNotFoundException) {
            $event->setResponse(new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND));

            return;
        }

        $validationFailure = $exception instanceof UnprocessableEntityHttpException
            ? $exception->getPrevious()
            : null;

        if ($validationFailure instanceof ValidationFailedException) {
            $event->setResponse($this->unprocessable($this->fromViolations($validationFailure->getViolations())));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Error'],
                $exception->getStatusCode(),
            ));
        }
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

    /** @param list<array{field: string, message: string}> $errors */
    private function unprocessable(array $errors): JsonResponse
    {
        return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
