<?php

declare(strict_types=1);

namespace PhDevUtils\Bir\Tests;

use PhDevUtils\Bir\PercentageTax;
use PHPUnit\Framework\TestCase;

final class PercentageTaxTest extends TestCase
{
    public function testCurrentRate3Percent(): void
    {
        $r = PercentageTax::compute(100000);
        $this->assertSame(0.03, $r['rate']);
        $this->assertSame(3000.0, $r['tax']);
    }

    public function test250kAtCurrentRate(): void
    {
        $this->assertSame(7500.0, PercentageTax::compute(250000)['tax']);
    }

    public function testZeroGross(): void
    {
        $this->assertSame(0.0, PercentageTax::compute(0)['tax']);
    }

    public function testCreateEra1PercentMidPeriod(): void
    {
        $r = PercentageTax::compute(100000, '2022-06-15');
        $this->assertSame(0.01, $r['rate']);
        $this->assertSame(1000.0, $r['tax']);
        $this->assertStringContainsString('RA 11534', $r['legalBasis']);
    }

    public function testLastDayOfReducedRate(): void
    {
        $this->assertSame(0.01, PercentageTax::compute(100000, '2023-06-30')['rate']);
    }

    public function testFirstDayOfRevert(): void
    {
        $this->assertSame(0.03, PercentageTax::compute(100000, '2023-07-01')['rate']);
    }

    public function testFirstDayOfReducedRate(): void
    {
        $this->assertSame(0.01, PercentageTax::compute(100000, '2020-07-01')['rate']);
    }

    public function testThrowsOnPre2020Date(): void
    {
        $this->expectException(\OutOfRangeException::class);
        PercentageTax::compute(100000, '2019-01-01');
    }

    public function testThrowsOnNegative(): void
    {
        $this->expectException(\OutOfRangeException::class);
        PercentageTax::compute(-1);
    }

    public function testThrowsOnNonFinite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PercentageTax::compute(NAN);
    }

    public function testRateHelper(): void
    {
        $this->assertSame(0.03, PercentageTax::rate());
        $this->assertSame(0.01, PercentageTax::rate('2021-03-15'));
    }
}
