<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\ValueObject;

use App\Pumukit\SchemaBundle\Document\Exception\UrlException;

final class Url
{
    private string $url;

    public function __construct(string $url)
    {
        $this->validate($url);
        $this->url = $url;
    }

    public function url(): string
    {
        return $this->url;
    }

    private function validate($url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new UrlException('Invalid URL');
        }
    }
}
