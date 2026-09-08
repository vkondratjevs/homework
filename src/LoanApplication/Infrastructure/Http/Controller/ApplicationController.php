<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Http\Controller;

use App\LoanApplication\Application\Command\CreateApplication;
use App\LoanApplication\Application\Handler\CreateApplicationHandler;
use App\LoanApplication\Infrastructure\Http\Dto\ApplicationView;
use App\LoanApplication\Infrastructure\Http\Dto\CreateApplicationRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Applications')]
final readonly class ApplicationController
{
    public function __construct(
        private CreateApplicationHandler $createApplicationHandler,
    ) {
    }

    #[OA\Post(
        description: 'Creates a new application in PENDING status and dispatches an asynchronous verification request to the credit bureau.',
        summary: 'Submit a loan application',
    )]
    #[OA\Response(
        response: 201,
        description: 'Application created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '01991f3a-1234-7abc-8def-0123456789ab'),
                new OA\Property(property: 'personalCode', type: 'string', example: '010199-12345'),
                new OA\Property(property: 'amount', type: 'string', example: '1000.00'),
                new OA\Property(property: 'term', type: 'integer', example: 24),
                new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
                new OA\Property(property: 'status', type: 'string', example: 'Pending'),
                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
            ],
        ),
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation failed',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'errors', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'field', type: 'string', example: 'personalCode'),
                        new OA\Property(property: 'message', type: 'string', example: 'personalCode must match the format DDMMYY-CCCCC.'),
                    ],
                )),
            ],
        ),
    )]
    #[Route('/applications', name: 'applications_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateApplicationRequest $dto): JsonResponse
    {
        $application = ($this->createApplicationHandler)(new CreateApplication(
            $dto->personalCode,
            $dto->amount,
            $dto->term,
            $dto->currency,
        ));

        return new JsonResponse(ApplicationView::fromEntity($application), Response::HTTP_CREATED);
    }
}
