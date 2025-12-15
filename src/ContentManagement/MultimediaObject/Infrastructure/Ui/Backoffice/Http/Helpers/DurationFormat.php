<?php

namespace App\ContentManagement\MultimediaObject\Infrastructure\Ui\Backoffice\Http\Helpers;

final class DurationFormat
{
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
