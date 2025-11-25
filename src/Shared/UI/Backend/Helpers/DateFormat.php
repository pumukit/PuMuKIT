<?php

namespace App\Shared\UI\Backend\Helpers;

final class DateFormat
{
    public static function format(\DateTimeInterface $date, string $format = 'Y/m/d'): string
    {
        return $date->format($format);
    }
}
