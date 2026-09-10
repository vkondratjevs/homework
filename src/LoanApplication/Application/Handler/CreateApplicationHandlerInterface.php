<?php

declare(strict_types=1);

namespace App\LoanApplication\Application\Handler;

use App\LoanApplication\Application\Command\CreateApplication;
use App\LoanApplication\Domain\Entity\Application;

interface CreateApplicationHandlerInterface
{
    public function __invoke(CreateApplication $command): Application;
}
