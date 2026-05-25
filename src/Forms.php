<?php

declare(strict_types=1);

namespace PhDevUtils\Bir;

final class Forms
{
    /** @return list<array{number:string,name:string,frequency:string,purpose:string,status:string,superseded_by?:string,superseded_on?:string,superseded_basis?:string}> */
    private static function all(): array
    {
        return DataLoader::load('forms')['forms'];
    }

    /**
     * List BIR forms, optionally filtered by status and/or frequency.
     *
     * @param array{status?: 'active'|'superseded', frequency?: 'monthly'|'quarterly'|'annual'|'event'} $filter
     */
    public static function list(array $filter = []): array
    {
        $out = self::all();
        if (isset($filter['status'])) {
            $out = array_filter($out, static fn ($f) => $f['status'] === $filter['status']);
        }
        if (isset($filter['frequency'])) {
            $out = array_filter($out, static fn ($f) => $f['frequency'] === $filter['frequency']);
        }
        return array_values($out);
    }

    /**
     * Look up a form by its number (case-insensitive, separator-tolerant).
     */
    public static function find(string $numberOrName): ?array
    {
        $q = strtolower(trim($numberOrName));
        if ($q === '') return null;
        $qNoSep = preg_replace('/[\s\-]+/', '', $q);

        // Pass 1: exact number match (with separator-tolerance).
        foreach (self::all() as $f) {
            $num = strtolower($f['number']);
            if ($num === $q) return $f;
            if (preg_replace('/[\s\-]+/', '', $num) === $qNoSep) return $f;
        }
        // Pass 2: exact name match (case-insensitive).
        foreach (self::all() as $f) {
            if (strtolower($f['name']) === $q) return $f;
        }
        return null;
    }
}
