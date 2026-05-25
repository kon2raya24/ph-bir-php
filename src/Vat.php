<?php

declare(strict_types=1);

namespace PhDevUtils\Bir;

final class Vat
{
    private const SOURCE = 'BIR Tax Code § 106-108 (VAT), as amended by RA 9337 + RA 10963 (TRAIN)';

    public static function rate(): float
    {
        return (float) DataLoader::load('vat')['rate'];
    }

    public static function threshold(): int
    {
        return (int) DataLoader::load('vat')['threshold_annual_gross'];
    }

    /**
     * Add 12% VAT to a net amount.
     *
     * @return array{gross:float, net:float, vat:float, rate:float, _source:string}
     */
    public static function add(float $net): array
    {
        if (!is_finite($net)) {
            throw new \InvalidArgumentException('Vat::add: amount must be a finite number');
        }
        $rate = self::rate();
        $vat = round($net * $rate, 2);
        $gross = round($net + $vat, 2);
        return [
            'gross' => $gross,
            'net' => round($net, 2),
            'vat' => $vat,
            'rate' => $rate,
            '_source' => self::SOURCE,
        ];
    }

    /**
     * Extract the VAT portion from a VAT-inclusive amount.
     *
     * @return array{gross:float, net:float, vat:float, rate:float, _source:string}
     */
    public static function extract(float $gross): array
    {
        if (!is_finite($gross)) {
            throw new \InvalidArgumentException('Vat::extract: amount must be a finite number');
        }
        $rate = self::rate();
        $net = round($gross / (1 + $rate), 2);
        $vat = round($gross - $net, 2);
        return [
            'gross' => round($gross, 2),
            'net' => $net,
            'vat' => $vat,
            'rate' => $rate,
            '_source' => self::SOURCE,
        ];
    }

    /**
     * Whether VAT registration is required given an annual gross sales figure.
     * Threshold: ₱3,000,000 (TRAIN, RA 10963).
     */
    public static function isRegistrationRequired(float $annualGross): bool
    {
        if (!is_finite($annualGross)) {
            return false;
        }
        return $annualGross > self::threshold();
    }
}
