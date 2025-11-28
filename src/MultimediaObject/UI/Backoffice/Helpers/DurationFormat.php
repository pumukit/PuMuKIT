<?php

namespace App\MultimediaObject\UI\Backoffice\Helpers;

final class DurationFormat
{
    private const ICON = '<i class="fas fa-clock"></i>';

    public static function convert(int $duration): string
    {
        if ($duration <= 0) {
            return '0:00';
        }

        $min = floor($duration / 60);
        $sec = $duration % 60;

        return sprintf('%d:%02d', $min, $sec);
    }
}
