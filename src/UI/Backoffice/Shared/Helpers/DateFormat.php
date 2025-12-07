<?php

namespace App\UI\Backoffice\Shared\Helpers;

final class DateFormat
{
    private const FORMAT_COMPLETE = 'Y-m-d H:i:s';
    private const FORMAT_DATE = 'Y-m-d';

    public static function format(\DateTimeInterface $date, string $format = self::FORMAT_DATE): string
    {
        return $date->format($format);
    }

    public static function formatComplete(\DateTimeInterface $date): string
    {
        return self::format($date, self::FORMAT_COMPLETE);
    }
}
