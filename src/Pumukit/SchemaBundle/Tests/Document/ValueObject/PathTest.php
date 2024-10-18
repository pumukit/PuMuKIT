<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Document\ValueObject;

use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Exception\PathException;
use Pumukit\SchemaBundle\Document\ValueObject\Path;

/**
 * @internal
 *
 * @coversNothing
 */
final class PathTest extends TestCase
{
    public function testCreate(): void
    {
        $path = Path::create(__DIR__);

        $this->assertInstanceOf(Path::class, $path);
        $this->assertEquals(__DIR__, $path->path());
    }

    public function testToString(): void
    {
        $path = Path::create(__DIR__);

        $this->assertEquals(__DIR__, (string) $path);
    }

    public function testException(): void
    {
        $this->expectException(PathException::class);
        $this->expectExceptionMessage('Invalid path');
        Path::create('invalid_path');
    }
}
