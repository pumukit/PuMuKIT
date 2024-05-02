<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\ValueObject;

use Pumukit\SchemaBundle\Document\Exception\UrlException;

class Url
{
    private string $url;

    protected function __construct(string $url)
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

    protected function validate($url): void
    {
        if (empty($url)) {
            return;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new UrlException('Invalid URL');
        }
    }
}
