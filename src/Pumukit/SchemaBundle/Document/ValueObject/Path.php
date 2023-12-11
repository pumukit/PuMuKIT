<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\ValueObject;

use App\Pumukit\SchemaBundle\Document\Exception\PathException;

final class Path
{
    private string $path;

    public function __construct(string $path)
    {
        $this->validate($path);
        $this->path = $path;
    }

    public function path(): string
    {
        return $this->path;
    }

    private function validate($path): void
    {
        if (empty($path)) {
            throw new PathException("Path cannot be empty");
        }

        $realPath = realpath($path);

        if ($realPath === false || !file_exists($realPath)) {
            throw new PathException("Invalid path");
        }
    }
}
