<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\ValueObject;

interface UrlInterface
{
    public function __toString(): string;

    public static function create(string $url): UrlInterface;

    public function url(): string;
}
