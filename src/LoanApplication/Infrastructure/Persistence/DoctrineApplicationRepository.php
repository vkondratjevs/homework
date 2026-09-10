<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Persistence;

use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Enum\ApplicationStatus;
use App\LoanApplication\Domain\Repository\ApplicationPage;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineApplicationRepository implements ApplicationRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Application $application): void
    {
        $this->entityManager->persist($application);
        $this->entityManager->flush();
    }

    public function updateStatus(Application $application, ApplicationStatus $previousStatus): bool
    {
        $affectedRows = $this->entityManager->getConnection()->executeStatement(
            sql: 'UPDATE applications SET status = :status, updated_at = :updatedAt WHERE id = :id AND status = :previousStatus',
            params: [
                'status' => $application->getStatus()->value,
                'updatedAt' => $application->getUpdatedAt(),
                'id' => $application->getId(),
                'previousStatus' => $previousStatus->value,
            ],
            types: [
                'updatedAt' => 'datetimetz_immutable',
                'id' => 'uuid',
            ],
        );

        return $affectedRows > 0;
    }

    public function findById(Uuid $id): ?Application
    {
        return $this->entityManager->find(Application::class, $id);
    }

    public function findPage(int $page, int $limit): ApplicationPage
    {
        $repository = $this->entityManager->getRepository(Application::class);

        /** @var list<Application> $items */
        $items = $repository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return new ApplicationPage(
            items: $items,
            total: $repository->count(),
        );
    }
}
