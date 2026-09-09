<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Http\Dto;

use App\LoanApplication\Domain\Entity\Application;
use JsonSerializable;

final readonly class ApplicationResponse implements JsonSerializable
{
    private function __construct(
        private string $id,
        private string $personalCode,
        private string $amount,
        private int $term,
        private string $currency,
        private string $status,
        private string $createdAt,
    ) {
    }

    public static function fromEntity(Application $application): self
    {
        return new self(
            id: $application->getId()->toRfc4122(),
            personalCode: $application->getPersonalCode(),
            amount: $application->getAmount(),
            term: $application->getTerm(),
            currency: $application->getCurrency(),
            status: $application->getStatus()->name,
            createdAt: $application->getCreatedAt()->format(\DATE_ATOM),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     personalCode: string,
     *     amount: string,
     *     term: int,
     *     currency: string,
     *     status: string,
     *     createdAt: string,
     *     }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'personalCode' => $this->personalCode,
            'amount' => $this->amount,
            'term' => $this->term,
            'currency' => $this->currency,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
        ];
    }
}
