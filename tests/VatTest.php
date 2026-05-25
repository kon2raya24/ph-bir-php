<?php

declare(strict_types=1);

namespace PhDevUtils\Bir\Tests;

use PhDevUtils\Bir\Vat;
use PHPUnit\Framework\TestCase;

final class VatTest extends TestCase
{
    public function testRateIs12Percent(): void
    {
        $this->assertSame(0.12, Vat::rate());
    }

    public function testThresholdIs3M(): void
    {
        $this->assertSame(3_000_000, Vat::threshold());
    }

    public function testAddVat(): void
    {
        $r = Vat::add(1000);
        $this->assertSame(1000.0, $r['net']);
        $this->assertSame(120.0, $r['vat']);
        $this->assertSame(1120.0, $r['gross']);
    }

    public function testAddVatDecimal(): void
    {
        $r = Vat::add(250.50);
        $this->assertSame(30.06, $r['vat']);
        $this->assertSame(280.56, $r['gross']);
    }

    public function testExtractVat(): void
    {
        $r = Vat::extract(1120);
        $this->assertSame(1000.0, $r['net']);
        $this->assertSame(120.0, $r['vat']);
    }

    public function testRoundTrip(): void
    {
        $added = Vat::add(500);
        $extracted = Vat::extract($added['gross']);
        $this->assertSame(500.0, $extracted['net']);
    }

    public function testIsRegistrationRequired(): void
    {
        $this->assertFalse(Vat::isRegistrationRequired(3_000_000));
        $this->assertTrue(Vat::isRegistrationRequired(3_000_001));
        $this->assertFalse(Vat::isRegistrationRequired(0));
    }

    public function testAddVatThrowsOnNonFinite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Vat::add(NAN);
    }

    public function testSourceIncluded(): void
    {
        $this->assertStringContainsString('RA 9337', Vat::extract(1120)['_source']);
    }
}
