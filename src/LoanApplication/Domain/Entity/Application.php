<?php

declare(strict_types=1);

namespace App\LoanApplication\Domain\Entity;

use App\LoanApplication\Domain\Enum\ApplicationStatus;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'applications')]
#[ORM\Index(name: 'idx_applications_status', columns: ['status'])]
class Application
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    // DDMMYY-CCCCC: 11 digits + separator = 12 characters.
    #[ORM\Column(length: 12)]
    private string $personalCode;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'smallint')]
    private int $term;

    #[ORM\Column(length: 3)]
    private string $currency;

    #[ORM\Column(type: 'smallint', enumType: ApplicationStatus::class)]
    private ApplicationStatus $status;

    #[ORM\Column(type: 'datetimetz_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetimetz_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        string $personalCode,
        string $amount,
        int $term,
        string $currency,
    ) {
        $now = new DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->id = Uuid::v7();
        $this->personalCode = $personalCode;
        $this->amount = $amount;
        $this->term = $term;
        $this->currency = $currency;
        $this->status = ApplicationStatus::Pending;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public static function create(string $personalCode, string $amount, int $term, string $currency): self
    {
        return new self($personalCode, $amount, $term, $currency);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPersonalCode(): string
    {
        return $this->personalCode;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getTerm(): int
    {
        return $this->term;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStatus(): ApplicationStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
