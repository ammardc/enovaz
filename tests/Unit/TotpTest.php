<?php

namespace Tests\Unit;

use App\Services\Totp;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class TotpTest extends TestCase
{
    public function test_rfc_vector_and_replay_prevention(): void
    {
        $totp = new Totp();
        $codeAt = new ReflectionMethod(Totp::class,'code');
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $this->assertSame('287082',$codeAt->invoke($totp,$secret,1));
        $currentStep = intdiv(time(),30);
        $currentCode = $codeAt->invoke($totp,$secret,$currentStep);
        $this->assertSame($currentStep,$totp->verify($secret,$currentCode));
        $this->assertNull($totp->verify($secret,$currentCode,$currentStep));
    }
}
