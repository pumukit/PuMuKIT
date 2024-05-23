<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Utils;

use Pumukit\SchemaBundle\Document\ValueObject\Path;

final class ImageRawUtils
{
    public const IMAGE_RAW_EXTENSIONS = [
        '3fr',
        'ari',
        'arw',
        'bay',
        'braw',
        'crw',
        'cr2',
        'cr3',
        'cap',
        'data',
        'dcs',
        'dcr',
        'dng',
        'drf',
        'eip',
        'erf',
        'fff',
        'gpr',
        'iiq',
        'k25',
        'kdc',
        'mdc',
        'mef',
        'mos',
        'mrw',
        'nef',
        'nrw',
        'obm',
        'orf',
        'pef',
        'ptx',
        'pxn',
        'r3d',
        'raf',
        'raw',
        'rwl',
        'rw2',
        'rwz',
        'sr2',
        'srf',
        'srw',
        'tif',
        'x3f',
    ];

    public static function isRawImage(Path $path): bool
    {
        $ext = pathinfo($path->path(), PATHINFO_EXTENSION);

        return in_array(strtolower($ext), self::IMAGE_RAW_EXTENSIONS);
    }

    public static function isRawImageFromString(string $path): bool
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return in_array(strtolower($ext), self::IMAGE_RAW_EXTENSIONS);
    }

    public static function extensions(): array
    {
        return array_map(static function ($element) { return '.'.$element; }, self::IMAGE_RAW_EXTENSIONS);
    }
}
