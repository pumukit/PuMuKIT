<?php

namespace App\Shared\Infrastructure\Ui\Backoffice\Helpers;

final class BooleanIcon
{
    public static function convert(bool $value): string
    {
        return $value
            ? '<i class="fas fa-check text-success"></i>'
            : '<i class="fas fa-times text-danger"></i>';
    }
}
