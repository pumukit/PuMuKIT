<?php

namespace App\Shared\Infrastructure\Ui\Backoffice\Helpers;

final class Thumbnail
{
    public static function convert(string $path): string
    {
        return '<img alt="" src="'.$path.'" style="width:40px; height:auto;">';
    }
}
