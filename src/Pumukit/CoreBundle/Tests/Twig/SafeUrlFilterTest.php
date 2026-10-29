<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Twig;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
#[CoversNothing]
class SafeUrlFilterTest extends KernelTestCase
{
    #[DataProvider('urls')]
    public function testTheHrefOnlyKeepsSafeUrls(?string $url, string $expected): void
    {
        $twig = static::getContainer()->get('twig');
        $html = $twig->createTemplate('<a href="{{ url|safe_url }}">x</a>')->render(['url' => $url]);

        $this->assertSame('<a href="'.$expected.'">x</a>', $html);
    }

    public static function urls(): iterable
    {
        yield 'https' => ['https://example.org/a?b=1&c=2', 'https://example.org/a?b=1&amp;c=2'];

        yield 'http' => ['http://example.org', 'http://example.org'];

        yield 'mailto' => ['mailto:info@example.org', 'mailto:info@example.org'];

        yield 'relative path' => ['/uploads/material/file.pdf', '/uploads/material/file.pdf'];

        yield 'protocol relative' => ['//cdn.example.org/file.pdf', '//cdn.example.org/file.pdf'];

        yield 'javascript' => ['javascript:alert(1)', '#'];

        yield 'javascript with a comment' => ['javascript://%0Aalert(1)', '#'];

        yield 'javascript in capitals with spaces' => [' JaVaScRiPt:alert(1)', '#'];

        yield 'javascript split by a tab' => ["java\tscript:alert(1)", '#'];

        yield 'data' => ['data:text/html,<script>alert(1)</script>', '#'];

        yield 'vbscript' => ['vbscript:msgbox(1)', '#'];

        yield 'empty' => [null, ''];
    }
}
