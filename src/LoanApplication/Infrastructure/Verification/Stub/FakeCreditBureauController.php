<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Verification\Stub;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class FakeCreditBureauController
{
    private const array SCENARIOS_BY_PERSONAL_CODE = [
        '010101-00001' => 'approve',
        '010101-00002' => 'reject',
        '010101-00429' => 'too_many_requests',
        '010101-00500' => 'server_error',
        '010101-00400' => 'bad_request',
        '010101-00408' => 'never_responds',
        '010101-00050' => 'random',
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $personalCode = is_array($payload) && is_string($payload['personalIdentificationNumber'] ?? null)
            ? $payload['personalIdentificationNumber']
            : '';

        $scenario = self::SCENARIOS_BY_PERSONAL_CODE[$personalCode] ?? 'approve';

        $this->logger->info('Fake credit bureau received a verification request.', [
            'personalCode' => $personalCode,
            'scenario' => $scenario,
        ]);

        return match ($scenario) {
            'approve' => new JsonResponse(['decision' => 'APPROVE']),
            'reject' => new JsonResponse(['decision' => 'REJECT']),
            'too_many_requests' => new JsonResponse(['error' => 'Too Many Requests'], Response::HTTP_TOO_MANY_REQUESTS),
            'server_error' => new JsonResponse(['error' => 'Internal Server Error'], Response::HTTP_INTERNAL_SERVER_ERROR),
            'bad_request' => new JsonResponse(['error' => 'Bad Request'], Response::HTTP_BAD_REQUEST),
            'never_responds' => $this->neverResponds(),
            'random' => $this->random(),
        };
    }

    private function neverResponds(): Response
    {
        sleep(10);

        return new JsonResponse(['decision' => 'APPROVE']);
    }

    private function random(): Response
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 25 => new JsonResponse(['error' => 'Too Many Requests'], Response::HTTP_TOO_MANY_REQUESTS),
            $roll <= 50 => new JsonResponse(['error' => 'Internal Server Error'], Response::HTTP_INTERNAL_SERVER_ERROR),
            $roll <= 75 => new JsonResponse(['decision' => 'APPROVE']),
            default => new JsonResponse(['decision' => 'REJECT']),
        };
    }
}
