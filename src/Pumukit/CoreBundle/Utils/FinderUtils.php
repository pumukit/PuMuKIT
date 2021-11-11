<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Utils;

use Symfony\Component\Finder\Finder;

final class FinderUtils 
{
    public static function getDirectoriesFromPath(string $path)
    {
        static::isValidPath($path);

        $finder = static::getFinder();
        $folders = $finder->depth('0')->directories()->in($path);

        return $folders;
    }

    private function getFinder()
    {
        return new Finder();
    }

    private function isValidPath(string $path) 
    {
        if (realpath($path)) {
            return true;
        }

        throw new \Exception("Path not valid.");
    }
}
