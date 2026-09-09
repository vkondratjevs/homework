<?php

declare(strict_types=1);

namespace App\Tests\Unit\LoanApplication\Infrastructure\Verification;

use App\LoanApplication\Domain\Verification\Exception\RetryableVerificationException;
use App\LoanApplication\Domain\Verification\Exception\TerminalVerificationException;
use App\LoanApplication\Domain\Verification\VerificationDecision;
use App\LoanApplication\Domain\Verification\VerificationRequest;
use App\LoanApplication\Infrastructure\Verification\CreditBureauClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Uid\Uuid;

final class CreditBureauClientTest extends TestCase
{
    public function testApproveDecision(): void
    {
        $decision = $this->verifyAgainst(new MockResponse('{"decision":"APPROVE"}', ['http_code' => 200]));

        self::assertSame(VerificationDecision::Approve, $decision);
    }

    public function testRejectDecision(): void
    {
        $decision = $this->verifyAgainst(new MockResponse('{"decision":"REJECT"}', ['http_code' => 200]));

        self::assertSame(VerificationDecision::Reject, $decision);
    }

    public function testTooManyRequestsIsRetryable(): void
    {
        $this->expectException(RetryableVerificationException::class);

        $this->verifyAgainst(new MockResponse('', ['http_code' => 429]));
    }

    public function testServerErrorIsRetryable(): void
    {
        $this->expectException(RetryableVerificationException::class);

        $this->verifyAgainst(new MockResponse('', ['http_code' => 503]));
    }

    public function testOtherClientErrorIsTerminal(): void
    {
        $this->expectException(TerminalVerificationException::class);

        $this->verifyAgainst(new MockResponse('', ['http_code' => 400]));
    }

    public function testUnexpectedTwoHundredBodyIsTerminal(): void
    {
        $this->expectException(TerminalVerificationException::class);

        $this->verifyAgainst(new MockResponse('{"decision":"MAYBE"}', ['http_code' => 200]));
    }

    public function testMalformedTwoHundredBodyIsTerminal(): void
    {
        $this->expectException(TerminalVerificationException::class);

        $this->verifyAgainst(new MockResponse('not json', ['http_code' => 200]));
    }

    public function testTransportFailureIsRetryable(): void
    {
        $this->expectException(RetryableVerificationException::class);

        $this->verifyAgainst(new MockResponse('', ['error' => 'Connection timed out']));
    }

    private function verifyAgainst(MockResponse $response): VerificationDecision
    {
        return new CreditBureauClient(new MockHttpClient($response))
            ->verify(new VerificationRequest(Uuid::v7(), '010199-12345', '1000.00', 24));
    }
}
