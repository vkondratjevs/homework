<?php

declare(strict_types=1);

namespace App\Tests\Unit\LoanApplication\Infrastructure\Messaging;

use App\LoanApplication\Application\Command\CheckBorrower;
use App\LoanApplication\Application\Command\RelayedCheckBorrower;
use App\LoanApplication\Infrastructure\Messaging\CheckBorrowerRelayHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class CheckBorrowerRelayHandlerTest extends TestCase
{
    public function testItForwardsTheMessageToTheVerificationTransport(): void
    {
        $applicationId = Uuid::v7();
        $message = new CheckBorrower($applicationId);

        $dispatchedMessage = null;

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with(self::callback(static function (RelayedCheckBorrower $relayed) use (&$dispatchedMessage): bool {
                $dispatchedMessage = $relayed;

                return true;
            }))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        $handler = new CheckBorrowerRelayHandler($messageBus, $this->createStub(LoggerInterface::class));

        $handler($message);

        self::assertInstanceOf(RelayedCheckBorrower::class, $dispatchedMessage);
        self::assertSame($applicationId, $dispatchedMessage->applicationId);
    }
}
