<?php

namespace App\MultimediaObject\UI\Backend\Helpers;

use Pumukit\SchemaBundle\Document\MultimediaObject;

final class TypeIcon
{
    public static function convert(int $type)
    {
        return match ($type) {
            MultimediaObject::TYPE_UNKNOWN => '<i class="fas fa-question text-warning"></i>',
            MultimediaObject::TYPE_VIDEO => '<i class="fas fa-video text-primary"></i>',
            MultimediaObject::TYPE_AUDIO => '<i class="fas fa-music text-info"></i>',
            MultimediaObject::TYPE_EXTERNAL => '<i class="fas fa-link"></i>',
            MultimediaObject::TYPE_LIVE => '<i class="fas fa-broadcast-tower text-danger"></i>',
            MultimediaObject::TYPE_IMAGE => '<i class="fas fa-image text-success"></i>',
            MultimediaObject::TYPE_DOCUMENT => '<i class="fas fa-file-alt text-muted"></i>',
            default => '<i class="fas fa-question text-warning"></i>',
        };
    }
}
