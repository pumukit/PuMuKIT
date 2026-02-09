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

    public static function isAllowed(?string $declaredMimeType, ?string $extension): bool
    {
        $extension = strtolower((string) $extension);
        $declaredMimeType = strtolower((string) $declaredMimeType);
        $allowedMimes = self::allowedMimeTypes();

        foreach ($allowedMimes as $allowed) {
            $allowed = strtolower($allowed);
            if (!empty($declaredMimeType)) {
                $pattern = str_replace(['/', '*'], ['\/', '.*'], $allowed);
                if (preg_match('/^'.$pattern.'$/', $declaredMimeType)) {
                    return true;
                }
            }

            if (str_contains($allowed, '*.'.$extension) || $allowed === $extension) {
                return true;
            }

            if (!empty($extension) && str_contains($allowed, $extension)) {
                return true;
            }
        }

        return false;
    }
}
