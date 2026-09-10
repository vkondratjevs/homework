<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Http\Controller;

use App\LoanApplication\Application\Command\CreateApplication;
use App\LoanApplication\Application\Handler\CreateApplicationHandlerInterface;
use App\LoanApplication\Domain\Exception\ApplicationNotFoundException;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use App\LoanApplication\Infrastructure\Http\Dto\ApplicationResponse;
use App\LoanApplication\Infrastructure\Http\Dto\CreateApplicationRequest;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;

#[OA\Tag(name: 'Applications')]
final class ApplicationController extends AbstractController
{
    private const int DEFAULT_PAGE_SIZE = 20;
    private const int MAX_PAGE_SIZE = 100;

    public function __construct(
        private readonly CreateApplicationHandlerInterface $createApplicationHandler,
        private readonly ApplicationRepositoryInterface $applications,
    ) {
    }

    #[OA\Get(summary: 'List applications')]
    #[OA\Parameter(name: 'page', description: 'Page number (1-based)', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1))]
    #[OA\Parameter(name: 'limit', description: 'Items per page', in: 'query', schema: new OA\Schema(type: 'integer', default: self::DEFAULT_PAGE_SIZE, maximum: self::MAX_PAGE_SIZE, minimum: 1))]
    #[OA\Response(
        response: 200,
        description: 'A page of applications',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '01991f3a-1234-7abc-8def-0123456789ab'),
                        new OA\Property(property: 'personalCode', type: 'string', example: '010199-12345'),
                        new OA\Property(property: 'amount', type: 'string', example: '1000.00'),
                        new OA\Property(property: 'term', type: 'integer', example: 24),
                        new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
                        new OA\Property(property: 'status', type: 'string', example: 'Pending'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
                    ],
                )),
                new OA\Property(property: 'page', type: 'integer', example: 1),
                new OA\Property(property: 'limit', type: 'integer', example: 20),
                new OA\Property(property: 'total', type: 'integer', example: 42),
            ],
        ),
    )]
    #[Route('/applications', name: 'applications_list', methods: [Request::METHOD_GET])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(self::MAX_PAGE_SIZE, max(1, $request->query->getInt('limit', self::DEFAULT_PAGE_SIZE)));

        $result = $this->applications->findPage($page, $limit);

        return new JsonResponse([
            'items' => array_map(ApplicationResponse::fromEntity(...), $result->items),
            'page' => $page,
            'limit' => $limit,
            'total' => $result->total,
        ]);
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
    #[Route('/applications', name: 'applications_create', methods: [Request::METHOD_POST])]
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

    #[OA\Get(summary: 'Get an application by id')]
    #[OA\Response(
        response: 200,
        description: 'Application details',
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
        response: 404,
        description: 'Application not found',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'error', type: 'string', example: 'Application "..." was not found.'),
        ]),
    )]
    #[Route('/applications/{id}', name: 'applications_get', requirements: ['id' => Requirement::UUID], methods: [Request::METHOD_GET])]
    public function get(Uuid $id): JsonResponse
    {
        $application = $this->applications->findById($id) ?? throw ApplicationNotFoundException::withId($id);

        return new JsonResponse(ApplicationResponse::fromEntity($application));
    }
}
