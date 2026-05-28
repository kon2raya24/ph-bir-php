<?php

declare(strict_types=1);

namespace PhDevUtils\Bir\Tests;

use PhDevUtils\Bir\IncomeTax;
use PHPUnit\Framework\TestCase;

final class IncomeTaxTest extends TestCase
{
    public function testExemptCeiling(): void
    {
        $this->assertSame(0.0, IncomeTax::graduated(250000)['tax']);
        $this->assertSame(0.0, IncomeTax::graduated(0)['tax']);
        $this->assertSame(0.0, IncomeTax::graduated(100000)['tax']);
    }

    public function testBracketBoundaries2023(): void
    {
        $this->assertSame(22500.0, IncomeTax::graduated(400000)['tax']);
        $this->assertSame(102500.0, IncomeTax::graduated(800000)['tax']);
        $this->assertSame(402500.0, IncomeTax::graduated(2000000)['tax']);
        $this->assertSame(2202500.0, IncomeTax::graduated(8000000)['tax']);
    }

    public function testMidBracket(): void
    {
        $r = IncomeTax::graduated(500000);
        $this->assertSame(42500.0, $r['tax']);
        $this->assertSame(0.2, $r['marginalRate']);
        $this->assertSame(2902500.0, IncomeTax::graduated(10000000)['tax']);
    }

    public function testHistorical2018To2022(): void
    {
        $this->assertSame(30000.0, IncomeTax::graduated(400000, '2022-12-31')['tax']);
        $this->assertSame(130000.0, IncomeTax::graduated(800000, '2020-06-01')['tax']);
        $this->assertSame(22500.0, IncomeTax::graduated(400000, '2023-01-01')['tax']);
    }

    public function testRejectsNegative(): void
    {
        $this->expectException(\OutOfRangeException::class);
        IncomeTax::graduated(-1);
    }

    public function testEightPercentPureSep(): void
    {
        $r = IncomeTax::eightPercent(1000000);
        $this->assertSame(750000.0, $r['base']);
        $this->assertSame(60000.0, $r['tax']);
        $this->assertTrue($r['eligible']);
    }

    public function testEightPercentMixedIncome(): void
    {
        $r = IncomeTax::eightPercent(1000000, true);
        $this->assertSame(1000000.0, $r['base']);
        $this->assertSame(80000.0, $r['tax']);
    }

    public function testEightPercentEligibility(): void
    {
        $this->assertFalse(IncomeTax::eightPercent(4000000)['eligible']);
        $this->assertTrue(IncomeTax::eightPercent(3000000)['eligible']);
        $this->assertSame(0.0, IncomeTax::eightPercent(250000)['tax']);
    }
}
