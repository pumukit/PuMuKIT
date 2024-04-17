<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document\ValueObject;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Exception\UrlException;
use Pumukit\SchemaBundle\Document\ValueObject\Url;

final class UrlTest extends TestCase
{
    public function testCreate(): void
    {
        $url = Url::create('https://www.example.com');

        $this->assertInstanceOf(Url::class, $url);
        $this->assertEquals('https://www.example.com', $url->url());
    }

    public function testToString(): void
    {
        $url = Url::create('https://www.example.com');

        $this->assertEquals('https://www.example.com', (string)$url);
    }

    public function testException(): void
    {
        $this->expectException(UrlException::class);
        $this->expectExceptionMessage('Invalid URL');
        Url::create('invalid_url');
    }
}
