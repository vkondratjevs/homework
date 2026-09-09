<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Persistence;

use App\LoanApplication\Domain\Entity\Application;
use App\LoanApplication\Domain\Repository\ApplicationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineApplicationRepository implements ApplicationRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ){}

    public function save(Application $application): void
    {
        $this->entityManager->persist($application);
        $this->entityManager->flush();
    }

    public function find(Uuid $id): ?Application
    {
        return $this->entityManager->find(Application::class, $id);
    }

    /**
     * @return array{items: list<Application>, total: int}
     */
    public function findPage(int $page, int $limit): array
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

        return [
            'items' => $items,
            'total' => $repository->count(),
        ];
    }
}
