<?php

namespace App\Shared\UI\Backoffice\Helpers;

final class DateFormat
{
    public static function format(\DateTimeInterface $date, string $format = 'Y/m/d'): string
    {
        return $date->format($format);
    }
}
