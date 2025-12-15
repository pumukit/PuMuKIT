<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ui\Backoffice\Http\Helpers;

class LinkFormat
{
    public static function generate(string $url, string $text): string
    {
        return '<a href="'.$url.'" target="_blank" style="text-decoration:none;">'.$text.'</a>';
    }
}
