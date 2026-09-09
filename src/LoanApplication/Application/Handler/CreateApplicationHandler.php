<?php

declare(strict_types=1);

namespace App\LoanApplication\Application\Handler;

use App\LoanApplication\Application\Command\CheckBorrower;
use App\LoanApplication\Application\Command\CreateApplication;
use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use App\LoanApplication\Domain\TransactionalSessionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CreateApplicationHandler
{
    public function __construct(
        private ApplicationRepositoryInterface $applicationRepository,
        private TransactionalSessionInterface $transactionalSession,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(CreateApplication $command): Application
    {
        $application = Application::create(
            personalCode: $command->personalCode,
            amount: $command->amount,
            term: $command->term,
            currency: $command->currency,
        );

        $this->transactionalSession->transactional(function () use ($application): void {
            $this->applicationRepository->save($application);
            $this->messageBus->dispatch(new CheckBorrower($application->getId()));
        });

        return $application;
    }
}
