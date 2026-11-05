<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Utils;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\CoreBundle\Utils\BlackListExtensions;

/**
 * @internal
 */
#[CoversNothing]
class BlackListExtensionsTest extends TestCase
{
    #[DataProvider('documentsTheBrowserCanRun')]
    public function testDocumentsTheBrowserCanRunAreRejected(string $extension): void
    {
        $this->assertTrue(BlackListExtensions::isBlackListed($extension));
    }

    public static function documentsTheBrowserCanRun(): iterable
    {
        foreach (['html', 'HTM', 'xhtml', 'xht', 'shtml', 'svg', 'svgz', 'xml', 'xsl', 'xslt', 'mht', 'mhtml', 'hta'] as $extension) {
            yield $extension => [$extension];
        }
    }

    #[DataProvider('wellFormedExtensions')]
    public function testWellFormedExtensions(string $extension): void
    {
        $this->assertTrue(BlackListExtensions::isWellFormed($extension));
    }

    public static function wellFormedExtensions(): iterable
    {
        yield 'no extension' => [''];

        foreach (['mp4', 'MP4', 'm4v', '3gp', 'mpeg2video'] as $extension) {
            yield $extension => [$extension];
        }
    }

    #[DataProvider('malformedExtensions')]
    public function testMalformedExtensions(string $extension): void
    {
        $this->assertFalse(BlackListExtensions::isWellFormed($extension));
    }

    public static function malformedExtensions(): iterable
    {
        foreach (['m p4', 'mp4;', 'mp4 ', 'mp4'."\n", 'a$b', 'a`b', 'a(b)', 'a|b', 'a&b', 'a"b', "a'b", 'ñ', 'tar~', 'muchtoolongname'] as $extension) {
            yield $extension => [$extension];
        }
    }

    public function testAMalformedExtensionIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BlackListExtensions::assertWellFormed('m p4');
    }

    #[DataProvider('materials')]
    public function testCommonMaterialsAreAccepted(string $extension): void
    {
        $this->assertFalse(BlackListExtensions::isBlackListed($extension));
    }

    public static function materials(): iterable
    {
        foreach (['pdf', 'vtt', 'srt', 'dfxp', 'mp4', 'png', 'jpg', 'zip', 'docx', 'pptx'] as $extension) {
            yield $extension => [$extension];
        }
    }
}
