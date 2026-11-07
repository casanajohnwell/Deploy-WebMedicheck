<?php

namespace App\Support;

class HealthCenter
{
    public static function forBarangay(?string $barangay): ?string
    {
        $clean = static::displayName($barangay);

        if ($clean === '') {
            return null;
        }

        return "Brgy. {$clean} Health Center";
    }

    public static function same(?string $left, ?string $right): bool
    {
        $left = static::normalize($left);
        $right = static::normalize($right);

        return $left !== '' && $left === $right;
    }

    public static function displayName(?string $value): string
    {
        if (! filled($value)) {
            return '';
        }

        return trim((string) preg_replace('/\s*\(Pob\.?\)$/i', '', (string) $value));
    }

    public static function normalize(?string $value): string
    {
        $value = strtolower(static::displayName($value));

        if ($value === '') {
            return '';
        }

        $value = str_replace(['.', ',', '-', '_'], ' ', $value);
        $value = preg_replace('/\b(city of|municipality of|town of)\b/i', '', $value) ?? $value;
        $value = preg_replace('/\s+(city|municipality)$/i', '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
