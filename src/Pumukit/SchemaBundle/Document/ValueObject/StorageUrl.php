<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\ValueObject;

use Pumukit\SchemaBundle\Document\Exception\UrlException;

final class StorageUrl extends Url
{
    protected function __construct(string $url)
    {
        $this->validate($url);
        parent::__construct($url);
    }

    public static function create(string $url): StorageUrl
    {
        return new self($url);
    }

    private function validate($url): void
    {
        if (empty($url)) {
            return;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) && !realpath($url)) {
            throw new UrlException('Invalid storage URL');
        }
    }
}
