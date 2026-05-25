<?php

declare(strict_types=1);

namespace PhDevUtils\Bir\Tests;

use PhDevUtils\Bir\Forms;
use PHPUnit\Framework\TestCase;

final class FormsTest extends TestCase
{
    public function testListAll(): void
    {
        $this->assertGreaterThanOrEqual(20, count(Forms::list()));
    }

    public function testListActiveExcludesSuperseded(): void
    {
        $active = Forms::list(['status' => 'active']);
        $numbers = array_column($active, 'number');
        $this->assertNotContains('2550M', $numbers);
        $this->assertContains('2550Q', $numbers);
    }

    public function testListSupersededIncludes2550M(): void
    {
        $superseded = Forms::list(['status' => 'superseded']);
        $this->assertNotEmpty($superseded);
        $numbers = array_column($superseded, 'number');
        $this->assertContains('2550M', $numbers);
    }

    public function testListByFrequency(): void
    {
        $annual = Forms::list(['frequency' => 'annual']);
        foreach ($annual as $f) {
            $this->assertSame('annual', $f['frequency']);
        }
        $numbers = array_column($annual, 'number');
        $this->assertContains('2316', $numbers);
        $this->assertContains('1700', $numbers);
    }

    public function testFind2316(): void
    {
        $f = Forms::find('2316');
        $this->assertNotNull($f);
        $this->assertSame('annual', $f['frequency']);
        $this->assertStringContainsString('Compensation', $f['name']);
    }

    public function testFindCaseInsensitive(): void
    {
        $this->assertSame('1601-C', Forms::find('1601-c')['number']);
        $this->assertSame('1601-C', Forms::find('1601-C')['number']);
    }

    public function testFindSeparatorTolerant(): void
    {
        $this->assertSame('2550Q', Forms::find('2550-Q')['number']);
        $this->assertSame('2550Q', Forms::find('2550 Q')['number']);
    }

    public function testSupersededMetadata(): void
    {
        $f = Forms::find('2550M');
        $this->assertSame('superseded', $f['status']);
        $this->assertSame('2550Q', $f['superseded_by']);
        $this->assertStringContainsString('EOPT', $f['superseded_basis']);
    }

    public function testReturnsNullForUnknown(): void
    {
        $this->assertNull(Forms::find('9999'));
        $this->assertNull(Forms::find(''));
    }
}
