<?php

namespace App\Shared\UI\Backend\Helpers;

final class TextTruncate
{
    private const SMALL_LENGTH = 20;
    private const MEDIUM_LENGTH = 50;
    private const LONG_LENGTH = 150;

    public static function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length).$suffix;
    }

    public static function small(string $text): string
    {
        return self::truncate($text, self::SMALL_LENGTH);
    }

    public static function medium(string $text): string
    {
        return self::truncate($text, self::MEDIUM_LENGTH);
    }

    public static function long(string $text): string
    {
        return self::truncate($text, self::LONG_LENGTH);
    }
}
