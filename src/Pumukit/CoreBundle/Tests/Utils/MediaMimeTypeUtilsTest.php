<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Utils;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\CoreBundle\Utils\MediaMimeTypeUtils;

/**
 * @internal
 */
#[CoversNothing]
class MediaMimeTypeUtilsTest extends TestCase
{
    public function testEveryAllowedTypeIsAMimeTypeOrAnExtensionTheUploaderUnderstands(): void
    {
        foreach (MediaMimeTypeUtils::allowedMimeTypes() as $type) {
            $this->assertTrue(str_contains($type, '/') || str_starts_with($type, '.'), $type);
        }
    }

    public function testTheUploaderAcceptsMxfVideos(): void
    {
        $this->assertContains('.mxf', MediaMimeTypeUtils::allowedVideoMimeTypes());
    }

    public function testAnMxfIsAllowedWithOrWithoutItsMimeType(): void
    {
        $this->assertTrue(MediaMimeTypeUtils::isAllowed('application/mxf', 'mxf'));
        $this->assertTrue(MediaMimeTypeUtils::isAllowed('', 'MXF'));
    }

    public function testAFileWithoutExtensionNeedsAnAllowedMimeType(): void
    {
        $this->assertTrue(MediaMimeTypeUtils::isAllowed('video/mp4', ''));
        $this->assertFalse(MediaMimeTypeUtils::isAllowed('application/x-msdownload', ''));
        $this->assertFalse(MediaMimeTypeUtils::isAllowed('', ''));
    }

    #[DataProvider('videoMimeTypes')]
    public function testVideoMimeTypes(string $mimeType, bool $isVideo): void
    {
        $this->assertSame($isVideo, MediaMimeTypeUtils::isVideo($mimeType));
    }

    public static function videoMimeTypes(): iterable
    {
        yield 'mp4' => ['video/mp4', true];

        yield 'mxf' => ['application/mxf', true];

        yield 'mxf in capitals' => ['Application/MXF', true];

        yield 'pdf' => ['application/pdf', false];

        yield 'audio' => ['audio/mpeg', false];

        yield 'empty' => ['', false];
    }
}
