<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\ValueObject;

use Pumukit\SchemaBundle\Document\Exception\UrlException;

final class Url implements UrlInterface
{
    private string $url;

    private function __construct(string $url)
    {
        $this->validate($url);
        $this->url = $url;
    }

    public function __toString(): string
    {
        return $this->url ?? '';
    }

    public static function create(string $url): Url
    {
        return new self($url);
    }

    public function url(): string
    {
        return $this->url;
    }

    private function validate($url): void
    {
        if (empty($url)) {
            return;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new UrlException('Invalid URL');
        }
    }
}
