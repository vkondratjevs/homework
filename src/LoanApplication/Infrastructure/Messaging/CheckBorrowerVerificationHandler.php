<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Messaging;

use App\LoanApplication\Application\Command\RelayedCheckBorrower;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'applications_verification')]
final readonly class CheckBorrowerVerificationHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RelayedCheckBorrower $message): void
    {
//        throw new \RuntimeException('Manual retry test.');

        $this->logger->info('Picked up CheckBorrower from the verification queue.', [
            'applicationId' => $message->applicationId->toRfc4122(),
        ]);
    }
}
