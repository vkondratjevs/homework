<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Messaging;

use App\LoanApplication\Application\Command\CheckBorrower;
use App\LoanApplication\Application\Command\RelayedCheckBorrower;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(fromTransport: 'applications_outbox')]
final readonly class CheckBorrowerRelayHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CheckBorrower $message): void
    {
        $this->logger->info('Relaying CheckBorrower from the outbox to the verification queue.', [
            'applicationId' => $message->applicationId->toRfc4122(),
        ]);

        $this->messageBus->dispatch(new RelayedCheckBorrower($message->applicationId));
    }
}
