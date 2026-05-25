<?php

declare(strict_types=1);

namespace PhDevUtils\Bir;

final class PercentageTax
{
    private const SOURCE = 'Tax Code § 116, as amended by RA 11534 (CREATE law)';

    private static function periods(): array
    {
        return DataLoader::load('percentage-tax')['periods'];
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

    /** @return array{rate:float, from:string, to:?string, legal_basis:string, description:string} */
    private static function findPeriod(string $isoDate): array
    {
        foreach (self::periods() as $p) {
            $matches = ($isoDate >= $p['from']) && ($p['to'] === null || $isoDate < $p['to']);
            if ($matches) {
                return $p;
            }
        }
        $earliest = self::periods()[0]['from'];
        throw new \OutOfRangeException(
            "percentageTax: date {$isoDate} is before the earliest period in the dataset ({$earliest}). " .
            "Pre-2020 historical computation is not supported.",
        );
    }

    /**
     * Compute percentage tax for a non-VAT-registered taxpayer.
     * Pass an `asOf` date to compute historical rates (e.g. 1% CREATE-era).
     *
     * @return array{grossReceipts:float, rate:float, tax:float, asOf:string, legalBasis:string, description:string, _source:string}
     */
    public static function compute(
        float $grossReceipts,
        \DateTimeInterface|string|null $asOf = null,
    ): array {
        if (!is_finite($grossReceipts)) {
            throw new \InvalidArgumentException('percentageTax: grossReceipts must be a finite number');
        }
        if ($grossReceipts < 0) {
            throw new \OutOfRangeException('percentageTax: grossReceipts must be non-negative');
        }
        $iso = self::toDateString($asOf);
        $period = self::findPeriod($iso);
        $tax = round($grossReceipts * $period['rate'], 2);
        return [
            'grossReceipts' => round($grossReceipts, 2),
            'rate' => (float) $period['rate'],
            'tax' => $tax,
            'asOf' => $iso,
            'legalBasis' => $period['legal_basis'],
            'description' => $period['description'],
            '_source' => self::SOURCE,
        ];
    }

    public static function rate(\DateTimeInterface|string|null $asOf = null): float
    {
        return (float) self::findPeriod(self::toDateString($asOf))['rate'];
    }
}
