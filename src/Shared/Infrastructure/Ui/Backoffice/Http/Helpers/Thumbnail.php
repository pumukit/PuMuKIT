<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ui\Backoffice\Http\Helpers;

final class Thumbnail
{
    public static function convert(string $path): string
    {
        return '<img alt="" src="'.$path.'" style="width:40px; height:auto;">';
    }
}
