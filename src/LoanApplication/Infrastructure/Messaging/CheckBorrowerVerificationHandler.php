<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Messaging;

use App\LoanApplication\Application\Command\RelayedCheckBorrower;
use App\LoanApplication\Application\Service\ApplicationResolver;
use App\LoanApplication\Domain\Enum\ApplicationStatus;
use App\LoanApplication\Domain\Exception\ApplicationNotFoundException;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use App\LoanApplication\Domain\Verification\BorrowerVerificationClientInterface;
use App\LoanApplication\Domain\Verification\Exception\TerminalVerificationException;
use App\LoanApplication\Domain\Verification\VerificationDecision;
use App\LoanApplication\Domain\Verification\VerificationRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'applications_verification')]
final readonly class CheckBorrowerVerificationHandler
{
    public function __construct(
        private ApplicationRepositoryInterface $applications,
        private BorrowerVerificationClientInterface $verificationClient,
        private ApplicationResolver $applicationResolver,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RelayedCheckBorrower $message): void
    {
        $application = $this->applications->findById($message->applicationId)
            ?? throw ApplicationNotFoundException::withId($message->applicationId);

        if (ApplicationStatus::Pending !== $application->getStatus()) {
            $this->logger->info('Skipping verification: application was already resolved.', [
                'applicationId' => $application->getId()->toRfc4122(),
                'status' => $application->getStatus()->name,
            ]);

            return;
        }

        $request = new VerificationRequest(
            applicationId: $application->getId(),
            personalCode: $application->getPersonalCode(),
            amount: $application->getAmount(),
            term: $application->getTerm(),
        );

        try {
            $decision = $this->verificationClient->verify($request);
        } catch (TerminalVerificationException $e) {
            $this->logger->warning('Credit bureau verification terminally failed.', [
                'applicationId' => $application->getId()->toRfc4122(),
                'error' => $e->getMessage(),
            ]);

            $isUpdated = $this->applicationResolver->failVerification($application);

            if (!$isUpdated) {
                $this->logger->info('Application status was not updated: it had already been resolved.', [
                    'applicationId' => $application->getId()->toRfc4122(),
                ]);
            }

            return;
        }

        $isUpdated = match ($decision) {
            VerificationDecision::Approve => $this->applicationResolver->approve($application),
            VerificationDecision::Reject => $this->applicationResolver->reject($application),
        };

        if (!$isUpdated) {
            $this->logger->info('Application status was not updated: it had already been resolved.', [
                'applicationId' => $application->getId()->toRfc4122(),
            ]);
        }
    }
}
