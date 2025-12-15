<?php

namespace App\ContentManagement\MultimediaObject\Infrastructure\Ui\Backoffice\Http\Helpers;

use Pumukit\SchemaBundle\Document\MultimediaObject;

final class StatusIcon
{
    public static function convert(int $status): string
    {
        return match ($status) {
            MultimediaObject::STATUS_PUBLISHED => '<i class="fas fa-check text-success"></i>',
            MultimediaObject::STATUS_BLOCKED => '<i class="fas fa-ban text-danger"></i>',
            MultimediaObject::STATUS_HIDDEN => '<i class="fas fa-eye-slash text-muted"></i>',
            MultimediaObject::STATUS_NEW => '<i class="fas fa-star text-primary"></i>',
            MultimediaObject::STATUS_PROTOTYPE => '<i class="fas fa-flask text-info"></i>',
            default => '<i class="fas fa-question text-warning"></i>',
        };
    }
}
