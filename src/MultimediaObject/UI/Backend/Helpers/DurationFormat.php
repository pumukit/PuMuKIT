<?php

namespace App\MultimediaObject\UI\Backend\Helpers;

final class DurationFormat
{
    public static function convert(int $duration): string
    {
        if ($duration > 0) {
            $min = floor($duration / 60);
            $seg = $duration % 60;

            if ($seg < 10) {
                $seg = '0'.$seg;
            }

            return $min."' ".$seg."''";
        }

        return "0''";
    }
}
