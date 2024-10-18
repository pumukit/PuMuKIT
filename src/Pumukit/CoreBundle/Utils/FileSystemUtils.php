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

    public static function createFolder(string $folder, int $mode = 0775): bool
    {
        if (!self::folderExists($folder)) {
            self::getFilesystem()->mkdir($folder, $mode);

            return true;
        }

        return false;
    }

    public static function exists($files): bool
    {
        return self::getFilesystem()->exists($files);
    }

    public static function copy(string $originFile, string $targetFile, bool $overwriteNewerFiles = false): void
    {
        self::getFilesystem()->copy($originFile, $targetFile, $overwriteNewerFiles);
    }

    public static function remove($files): void
    {
        self::getFilesystem()->remove($files);
    }
}
