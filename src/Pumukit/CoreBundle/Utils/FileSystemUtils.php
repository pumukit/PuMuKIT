<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Utils;

use Symfony\Component\Filesystem\Filesystem;

final class FileSystemUtils
{
    public static function getFilesystem(): Filesystem
    {
        return new Filesystem();
    }

    public static function folderExists(string $folder): bool
    {
        return self::getFilesystem()->exists($folder);
    }

    public static function createFolder(string $folder): bool
    {
        if (!self::folderExists($folder)) {
            self::getFilesystem()->mkdir($folder);

            return true;
        }

        return false;
    }
}
