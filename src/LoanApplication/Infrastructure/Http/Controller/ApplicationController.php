<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Http\Controller;

use App\LoanApplication\Application\Command\CreateApplication;
use App\LoanApplication\Application\Handler\CreateApplicationHandler;
use App\LoanApplication\Infrastructure\Http\Dto\ApplicationResponse;
use App\LoanApplication\Infrastructure\Http\Dto\CreateApplicationRequest;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Applications')]
final class ApplicationController extends AbstractController
{
    public function __construct(
        private readonly CreateApplicationHandler $createApplicationHandler,
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
    public function create(#[MapRequestPayload] CreateApplicationRequest $dto, LoggerInterface $logger): JsonResponse
    {
        $application = ($this->createApplicationHandler)(new CreateApplication(
            personalCode: $dto->personalCode,
            amount: $dto->amount,
            term: $dto->term,
            currency: $dto->currency,
        ));

        $logger->debug('Application created', [
            'applicationId' => $application->getId()->toRfc4122(),
            'personalCode' => substr($application->getPersonalCode(), 0, -5) . '*****',
            'amount' => $application->getAmount(),
            'term' => $application->getTerm(),
            'currency' => $application->getCurrency(),
            'status' => $application->getStatus()->name,
            'createdAt' => $application->getCreatedAt()->format(\DATE_ATOM),
        ]);

        return new JsonResponse(ApplicationResponse::fromEntity($application), Response::HTTP_CREATED);
    }
}
