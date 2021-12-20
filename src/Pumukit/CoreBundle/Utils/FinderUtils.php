<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Utils;

use Symfony\Component\Finder\Finder;

final class FinderUtils
{
    public static function getDirectoriesFromPath(string $path): Finder
    {
        self::isValidPath($path);

        $finder = self::getFinder();

        return $finder->depth('0')->directories()->in($path);
    }

    public static function getFinder(): Finder
    {
        return new Finder();
    }

    public static function isValidPath(string $path): bool
    {
        if (realpath($path)) {
            return true;
        }

        throw new \Exception('Path not valid.');
    }
}
