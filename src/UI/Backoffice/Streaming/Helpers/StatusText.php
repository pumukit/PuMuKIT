<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Streaming\Helpers;

class StatusText
{
    public static function convert(bool $status): string
    {
        $status = (int) $status;

        return match ($status) {
            0 => 'On hold',
            1 => 'Live broadcasting',
        };
    }
}
