<?php

declare(strict_types=1);

namespace PhDevUtils\Bir\Tests;

use PHPUnit\Framework\TestCase;
use PhDevUtils\Bir\Ewt;

final class EwtTest extends TestCase
{
    public function testListCategories(): void
    {
        $cats = Ewt::listCategories();
        $this->assertCount(8, $cats);
        $keys = array_map(static fn ($c) => $c['key'], $cats);
        $this->assertContains('professional_individual', $keys);
        $this->assertContains('services_twa', $keys);
    }

    public function testFlatRates(): void
    {
        $this->assertSame(0.05, Ewt::rate('rental'));
        $this->assertSame(0.02, Ewt::rate('contractor'));
        $this->assertSame(0.01, Ewt::rate('goods_twa'));
        $this->assertSame(0.02, Ewt::rate('services_twa'));
    }

    public function testThresholdDefaultsToHigherRate(): void
    {
        $this->assertSame(0.10, Ewt::rate('professional_individual'));
        $this->assertSame(0.15, Ewt::rate('professional_corporate'));
    }

    public function testIndividualLowerRateConditions(): void
    {
        $this->assertSame(0.05, Ewt::rate('professional_individual', ['swornDeclaration' => true, 'payeeAnnualGross' => 800000]));
        $this->assertSame(0.10, Ewt::rate('professional_individual', ['swornDeclaration' => true, 'payeeAnnualGross' => 3500000]));
        $this->assertSame(0.10, Ewt::rate('professional_individual', ['swornDeclaration' => true, 'payeeAnnualGross' => 800000, 'vatRegistered' => true]));
        $this->assertSame(0.10, Ewt::rate('professional_individual', ['payeeAnnualGross' => 800000]));
    }

    public function testCorporateLowerRateConditions(): void
    {
        $this->assertSame(0.10, Ewt::rate('professional_corporate', ['swornDeclaration' => true, 'payeeAnnualGross' => 500000]));
        $this->assertSame(0.15, Ewt::rate('professional_corporate', ['swornDeclaration' => true, 'payeeAnnualGross' => 900000]));
        // VAT registration does not force the higher rate for corporations
        $this->assertSame(0.10, Ewt::rate('professional_corporate', ['swornDeclaration' => true, 'payeeAnnualGross' => 500000, 'vatRegistered' => true]));
    }

    public function testComputeFlat(): void
    {
        $r = Ewt::compute(50000, 'rental');
        $this->assertSame(0.05, $r['rate']);
        $this->assertSame(2500.0, $r['tax']);
        $this->assertSame(47500.0, $r['net']);
    }

    public function testComputeRoundsToCentavos(): void
    {
        $r = Ewt::compute(33333.33, 'contractor');
        $this->assertSame(666.67, $r['tax']);
        $this->assertSame(32666.66, $r['net']);
    }

    public function testComputeHonorsThresholdOptions(): void
    {
        $this->assertSame(10000.0, Ewt::compute(100000, 'professional_individual')['tax']);
        $this->assertSame(5000.0, Ewt::compute(100000, 'professional_individual', ['swornDeclaration' => true, 'payeeAnnualGross' => 800000])['tax']);
    }

    public function testRejectsBadInput(): void
    {
        $this->expectException(\OutOfRangeException::class);
        Ewt::compute(-1, 'rental');
    }

    public function testUnknownCategoryThrows(): void
    {
        $this->expectException(\OutOfRangeException::class);
        Ewt::rate('bogus');
    }
}
