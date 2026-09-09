<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Verification;

use App\LoanApplication\Domain\Verification\BorrowerVerificationClientInterface;
use App\LoanApplication\Domain\Verification\Exception\RetryableVerificationException;
use App\LoanApplication\Domain\Verification\Exception\TerminalVerificationException;
use App\LoanApplication\Domain\Verification\VerificationDecision;
use App\LoanApplication\Domain\Verification\VerificationRequest;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class CreditBureauClient implements BorrowerVerificationClientInterface
{
    public function __construct(
        #[Autowire(service: 'credit_bureau')]
        private HttpClientInterface $httpClient,
    ) {
    }

    public function verify(VerificationRequest $request): VerificationDecision
    {
        try {
            $response = $this->httpClient->request('POST', '/v1/verifications', [
                'json' => [
                    'applicationId' => $request->applicationId->toRfc4122(),
                    'personalIdentificationNumber' => $request->personalCode,
                    'amount' => $request->amount,
                    'term' => $request->term,
                ],
            ]);

            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw RetryableVerificationException::fromTransportFailure($e);
        }

        if (429 === $statusCode || $statusCode >= 500) {
            throw RetryableVerificationException::fromStatusCode($statusCode);
        }

        if ($statusCode >= 400) {
            throw TerminalVerificationException::fromStatusCode($statusCode);
        }

        try {
            $data = $response->toArray();
        } catch (\Throwable) {
            throw TerminalVerificationException::unexpectedResponseShape();
        }

        return match ($data['decision'] ?? null) {
            'APPROVE' => VerificationDecision::Approve,
            'REJECT' => VerificationDecision::Reject,
            default => throw TerminalVerificationException::unexpectedResponseShape(),
        };
    }
}
