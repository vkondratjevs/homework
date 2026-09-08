<?php

declare(strict_types=1);

namespace App\LoanApplication\Infrastructure\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateApplicationRequest
{
    public function __construct(
        #[Assert\NotBlank]
        // Pre-2017 Latvian personal code: DDMMYY-CCCCC, structural check only (no checksum).
        #[Assert\Regex(
            pattern: '/^\d{6}-\d{5}$/',
            message: 'personalCode must match the format DDMMYY-CCCCC.',
        )]
        public string $personalCode,

        #[Assert\NotBlank]
        // Kept as a string (not float) to avoid binary floating-point rounding on money.
        #[Assert\Regex(
            pattern: '/^\d{1,4}(\.\d{1,2})?$/',
            message: 'amount must be a decimal number with up to 2 fraction digits.',
        )]
        #[Assert\Range(min: '100.00', max: '5000.00')]
        public string $amount,

        #[Assert\NotNull]
        #[Assert\Range(min: 10, max: 30)]
        public int $term,

        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['EUR'])]
        public string $currency,
    ) {
    }
}
