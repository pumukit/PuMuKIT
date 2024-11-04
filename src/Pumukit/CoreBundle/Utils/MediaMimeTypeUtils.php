<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Utils;

final class MediaMimeTypeUtils
{
    public static function allowedMimeTypes(): array
    {
        return array_merge(
            self::allowedAudioMimeTypes(),
            self::allowedVideoMimeTypes(),
            self::allowedImageMimeTypes(),
            self::allowedDocumentMimeTypes()
        );
    }

    public static function allowedImageMimeTypes(): array
    {
        $rawExtensions = ImageRawUtils::extensions();
        $rawExtensions[] = 'image/*';

        return $rawExtensions;
    }

    public static function allowedVideoMimeTypes(): array
    {
        return ['video/*', '*.mxf'];
    }

    public static function allowedAudioMimeTypes(): array
    {
        return ['audio/*'];
    }

    public static function allowedDocumentMimeTypes(): array
    {
        return ['application/pdf'];
    }
}
