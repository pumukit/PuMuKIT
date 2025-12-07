<?php

namespace App\UI\Backoffice\Shared\Helpers;

final class Thumbnail
{
    public static function convert(string $path): string
    {
        return '<img alt="" src="'.$path.'" style="width:40px; height:auto;">';
    }
}
