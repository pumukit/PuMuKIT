<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Utils;

use Symfony\Component\Finder\Finder;

final class FinderUtils
{
    public static function directoriesFromPath(string $path): Finder
    {
        (new FinderUtils())->isValidPath($path);

        $finder = (new FinderUtils())->finder();

        return $finder->depth('0')->directories()->in($path);
    }

    public static function findFilePathname(string $path, string $fileName): ?string
    {
        $finder = (new FinderUtils())->finder();

        $finder->files()->in($path)->name($fileName.'.*');

        if (!$finder->hasResults()) {
            return null;
        }

        foreach ($finder->files() as $file) {
            return $file->getPathname();
        }

        return null;
    }

    private function finder(): Finder
    {
        return new Finder();
    }

    private function isValidPath(string $path): void
    {
        if (realpath($path)) {
            return;
        }

        throw new \Exception('Path not valid.');
    }
}
