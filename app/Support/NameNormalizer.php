<?php

declare(strict_types=1);

namespace App\Support;

final class NameNormalizer
{
    public static function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = trim($value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);

        return $value;
    }
}
