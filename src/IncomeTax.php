<?php

declare(strict_types=1);

namespace PhDevUtils\Bir;

/**
 * Individual (annual) income tax under TRAIN — graduated rates (date-aware:
 * 2018–2022 vs 2023-onward schedules) and the 8% optional flat tax for
 * self-employed individuals / professionals (gross <= ₱3M).
 */
final class IncomeTax
{
    private const SOURCE = 'NIRC § 24(A)(2), as amended by RA 10963 (TRAIN Law)';

    private static function data(): array
    {
        return DataLoader::load('income-tax');
    }

    private static function toDateString(\DateTimeInterface|string|null $d): string
    {
        if ($d === null) {
            return (new \DateTimeImmutable())->format('Y-m-d');
        }
        if (is_string($d)) {
            return substr($d, 0, 10);
        }
        return $d->format('Y-m-d');
    }

    private static function findSchedule(string $isoDate): array
    {
        $schedules = self::data()['schedules'];
        foreach ($schedules as $s) {
            $matches = ($isoDate >= $s['from']) && ($s['to'] === null || $isoDate < $s['to']);
            if ($matches) {
                return $s;
            }
        }
        $earliest = $schedules[0]['from'];
        throw new \OutOfRangeException(
            "incomeTaxGraduated: date {$isoDate} is before the earliest schedule ({$earliest}). " .
            'Pre-2018 historical computation is not supported.',
        );
    }

    /**
     * Graduated annual income tax. Rates looked up by `asOf` date.
     *
     * @return array{taxableIncome:float, tax:float, marginalRate:float, asOf:string, legalBasis:string, _source:string}
     */
    public static function graduated(
        float $taxableIncome,
        \DateTimeInterface|string|null $asOf = null,
    ): array {
        if (!is_finite($taxableIncome)) {
            throw new \InvalidArgumentException('incomeTaxGraduated: taxableIncome must be a finite number');
        }
        if ($taxableIncome < 0) {
            throw new \OutOfRangeException('incomeTaxGraduated: taxableIncome must be non-negative');
        }
        $iso = self::toDateString($asOf);
        $schedule = self::findSchedule($iso);

        $applicable = $schedule['brackets'][0];
        foreach ($schedule['brackets'] as $b) {
            if ($taxableIncome > $b['over']) {
                $applicable = $b;
            } else {
                break;
            }
        }
        $tax = round($applicable['base'] + $applicable['rate'] * ($taxableIncome - $applicable['over']), 2);
        return [
            'taxableIncome' => round($taxableIncome, 2),
            'tax' => $tax,
            'marginalRate' => (float) $applicable['rate'],
            'asOf' => $iso,
            'legalBasis' => $schedule['legal_basis'],
            '_source' => self::SOURCE,
        ];
    }

    /**
     * 8% optional flat tax for self-employed / professionals (gross <= ₱3M),
     * in lieu of graduated income tax AND the 3% percentage tax.
     * Pure SEP: 8% × (gross − ₱250k). Mixed-income: 8% × gross.
     *
     * @return array{gross:float, rate:float, base:float, tax:float, eligible:bool, mixedIncome:bool, note:string, _source:string}
     */
    public static function eightPercent(float $gross, bool $mixedIncome = false): array
    {
        if (!is_finite($gross)) {
            throw new \InvalidArgumentException('incomeTax8: gross must be a finite number');
        }
        if ($gross < 0) {
            throw new \OutOfRangeException('incomeTax8: gross must be non-negative');
        }
        $eight = self::data()['eight_percent'];
        $base = $mixedIncome ? $gross : max(0, $gross - $eight['exemption']);
        return [
            'gross' => round($gross, 2),
            'rate' => (float) $eight['rate'],
            'base' => round($base, 2),
            'tax' => round($base * $eight['rate'], 2),
            'eligible' => $gross <= $eight['gross_cap'],
            'mixedIncome' => $mixedIncome,
            'note' => 'In lieu of graduated income tax and the 3% percentage tax. Not available if gross > ₱3,000,000 or if VAT-registered.',
            '_source' => (string) $eight['legal_basis'],
        ];
    }
}
