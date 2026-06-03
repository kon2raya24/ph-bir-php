<?php

declare(strict_types=1);

namespace PhDevUtils\Bir;

/**
 * Expanded (creditable) Withholding Tax — common categories and rates under BIR
 * RR 11-2018 (amending RR 2-98), TRAIN era. Mirrors the JS package's `ewt`
 * module (@ph-dev-utils/bir).
 */
final class Ewt
{
    private const SOURCE = 'BIR RR 11-2018 (amending RR 2-98), TRAIN (RA 10963)';

    /** @return list<array<string, mixed>> */
    private static function categories(): array
    {
        /** @var array{categories: list<array<string, mixed>>} $d */
        $d = DataLoader::load('ewt');

        return $d['categories'];
    }

    /** @return array<string, mixed>|null */
    private static function find(string $key): ?array
    {
        foreach (self::categories() as $c) {
            if ($c['key'] === $key) {
                return $c;
            }
        }

        return null;
    }

    /** Provenance + scope note for the bundled EWT table. */
    public static function meta(): array
    {
        /** @var array{_meta: array<string,mixed>} $d */
        $d = DataLoader::load('ewt');

        return $d['_meta'];
    }

    /** @return list<array<string, mixed>> */
    public static function listCategories(): array
    {
        return self::categories();
    }

    /**
     * The applicable EWT rate for a category. Threshold categories return the
     * higher rate by default; the lower rate applies only with a sworn
     * declaration, the payee's annual gross at or below the threshold, and (for
     * individuals) a non-VAT-registered payee.
     *
     * @param array{payeeAnnualGross?: float|int|null, swornDeclaration?: bool, vatRegistered?: bool} $opts
     */
    public static function rate(string $categoryKey, array $opts = []): float
    {
        $c = self::find($categoryKey);
        if ($c === null) {
            throw new \OutOfRangeException("ewtRate: unknown EWT category \"{$categoryKey}\"");
        }
        if ($c['kind'] === 'flat') {
            return (float) $c['rate'];
        }
        $gross = $opts['payeeAnnualGross'] ?? INF;
        $qualifiesLow =
            ($opts['swornDeclaration'] ?? false) === true
            && $gross <= $c['threshold']
            && ! (($c['vatForcesHigh'] ?? false) === true && ($opts['vatRegistered'] ?? false) === true);

        return (float) ($qualifiesLow ? $c['lowRate'] : $c['highRate']);
    }

    /**
     * Compute the expanded (creditable) withholding tax on an income payment.
     *
     * @param array{payeeAnnualGross?: float|int|null, swornDeclaration?: bool, vatRegistered?: bool} $opts
     * @return array{amount: float, categoryKey: string, label: string, rate: float, tax: float, net: float, _source: string}
     */
    public static function compute(float $amount, string $categoryKey, array $opts = []): array
    {
        if (! is_finite($amount)) {
            throw new \InvalidArgumentException('computeEWT: amount must be a finite number');
        }
        if ($amount < 0) {
            throw new \OutOfRangeException('computeEWT: amount must be non-negative');
        }
        $c = self::find($categoryKey);
        if ($c === null) {
            throw new \OutOfRangeException("computeEWT: unknown EWT category \"{$categoryKey}\"");
        }
        $rate = self::rate($categoryKey, $opts);
        $a = round($amount, 2);
        $tax = round($a * $rate, 2);

        return [
            'amount' => $a,
            'categoryKey' => $categoryKey,
            'label' => (string) $c['label'],
            'rate' => $rate,
            'tax' => $tax,
            'net' => round($a - $tax, 2),
            '_source' => self::SOURCE,
        ];
    }
}
