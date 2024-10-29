<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\ValueObject;

use Pumukit\SchemaBundle\Document\Exception\PathException;

final class Path
{
    private string $path;

    private function __construct(string $path)
    {
        $this->validate($path);
        $this->path = $path;
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public static function create(string $path): Path
    {
        return new self($path);
    }

    public function path(): string
    {
        return $this->path;
    }

    private function validate($path): void
    {
        if (empty($path)) {
            return;
        }

        $realPath = realpath($path);

        if (false === $realPath || !file_exists($realPath)) {
            throw new PathException('Invalid path');
        }
    }
}
