<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Persistence;

use App\LoanApplication\Application\Command\CheckBorrower;
use App\LoanApplication\Application\Command\CreateApplication;
use App\LoanApplication\Application\Handler\CreateApplicationHandlerInterface;
use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class DoctrineCreateApplicationHandler implements CreateApplicationHandlerInterface
{
    public function __construct(
        private ApplicationRepositoryInterface $applicationRepository,
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $entityManager,
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

        $this->entityManager->wrapInTransaction(function () use ($application): void {
            $this->applicationRepository->save($application);
            $this->messageBus->dispatch(new CheckBorrower($application->getId()));
        });

        return $application;
    }
}
