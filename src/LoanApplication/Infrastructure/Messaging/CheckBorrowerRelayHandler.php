<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Messaging;

use App\LoanApplication\Application\Command\CheckBorrower;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'applications_outbox')]
final readonly class CheckBorrowerRelayHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CheckBorrower $message): void
    {
        $this->logger->info('Picked up CheckBorrower from the outbox.', [
            'applicationId' => $message->applicationId->toRfc4122(),
        ]);
    }
}
