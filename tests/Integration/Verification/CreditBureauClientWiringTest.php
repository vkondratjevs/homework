<?php

declare(strict_types=1);

namespace App\Tests\Integration\Verification;

use App\LoanApplication\Domain\Verification\Exception\RetryableVerificationException;
use App\LoanApplication\Domain\Verification\Exception\TerminalVerificationException;
use App\LoanApplication\Domain\Verification\VerificationDecision;
use App\LoanApplication\Domain\Verification\VerificationRequest;
use App\LoanApplication\Infrastructure\Verification\CreditBureauClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Uid\Uuid;

final class CreditBureauClientWiringTest extends TestCase
{
    public function testApproveMagicCodeReachesTheFakeVendorOverRealHttp(): void
    {
        $decision = $this->creditBureauClient()->verify(new VerificationRequest(Uuid::v7(), '010101-00001', '1000.00', 24));

        self::assertSame(VerificationDecision::Approve, $decision);
    }

    public function testRejectMagicCodeReachesTheFakeVendorOverRealHttp(): void
    {
        $decision = $this->creditBureauClient()->verify(new VerificationRequest(Uuid::v7(), '010101-00002', '1000.00', 24));

        self::assertSame(VerificationDecision::Reject, $decision);
    }

    public function testTooManyRequestsMagicCodeIsClassifiedAsRetryable(): void
    {
        $this->expectException(RetryableVerificationException::class);

        $this->creditBureauClient()->verify(new VerificationRequest(Uuid::v7(), '010101-00429', '1000.00', 24));
    }

    public function testBadRequestMagicCodeIsClassifiedAsTerminal(): void
    {
        $this->expectException(TerminalVerificationException::class);

        $this->creditBureauClient()->verify(new VerificationRequest(Uuid::v7(), '010101-00400', '1000.00', 24));
    }

    private function creditBureauClient(): CreditBureauClient
    {
        $baseUri = $_ENV['CREDIT_BUREAU_BASE_URL'] ?? null;
        self::assertIsString($baseUri, 'CREDIT_BUREAU_BASE_URL must be set for this test to reach the fake vendor.');

        return new CreditBureauClient(HttpClient::create(['base_uri' => $baseUri, 'timeout' => 15]));
    }
}
