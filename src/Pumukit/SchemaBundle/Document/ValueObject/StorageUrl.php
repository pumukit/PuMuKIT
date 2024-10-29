<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\ValueObject;

final class StorageUrl implements UrlInterface
{
    private string $url;

    private function __construct(string $url)
    {
        $this->url = $url;
    }

    public function __toString(): string
    {
        return $this->url ?? '';
    }

    public static function create(string $url): StorageUrl
    {
        return new self($url);
    }

    public function url(): string
    {
        return $this->url;
    }
}
