<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\CoreBundle\Services\FrameAncestorsProviderInterface;
use Pumukit\CoreBundle\Services\FrameAncestorsService;

/**
 * @internal
 */
#[CoversNothing]
class FrameAncestorsServiceTest extends TestCase
{
    #[DataProvider('policies')]
    public function testThePolicyTranslatesTheDomains(array $domains, string $extra, string $expected): void
    {
        $this->assertSame($expected, $this->service($domains, $extra)->policy());
    }

    public static function policies(): iterable
    {
        yield 'no domains' => [[], '', "frame-ancestors 'self'"];

        yield 'literal host' => [['moodle.uni.es'], '', "frame-ancestors 'self' moodle.uni.es"];

        yield 'escaped host' => [['moodle\.uni\.es'], '', "frame-ancestors 'self' moodle.uni.es"];

        yield 'subdomains' => [['.*\.uni\.es'], '', "frame-ancestors 'self' *.uni.es"];

        yield 'anchored' => [['^moodle\.uni\.es$'], '', "frame-ancestors 'self' moodle.uni.es"];

        yield 'any host from the bundle' => [['*'], '', 'frame-ancestors *'];

        yield 'any host from the variable' => [[], '*', 'frame-ancestors *'];

        yield 'extra domains' => [['moodle.uni.es'], ' https://intranet.uni.es  portal.uni.es ', "frame-ancestors 'self' https://intranet.uni.es portal.uni.es moodle.uni.es"];

        yield 'repeated domains' => [['moodle.uni.es', 'moodle\.uni\.es'], '', "frame-ancestors 'self' moodle.uni.es"];

        yield 'regular expression without translation' => [['(a|b)\.uni\.es'], '', "frame-ancestors 'self'"];
    }

    public function testTheVariableMayNotBeDefined(): void
    {
        $this->assertSame("frame-ancestors 'self'", (new FrameAncestorsService([], null))->policy());
    }

    public function testADomainWithoutTranslationIsReported(): void
    {
        $this->assertSame(['(a|b)\.uni\.es'], $this->service(['(a|b)\.uni\.es'])->untranslatable());
    }

    #[DataProvider('hosts')]
    public function testAllows(string $host, bool $expected): void
    {
        $service = $this->service(['moodle.uni.es', '.*\.campus\.es'], 'https://intranet.uni.es');

        $this->assertSame($expected, $service->allows($host));
    }

    public static function hosts(): iterable
    {
        yield 'literal' => ['MOODLE.uni.es', true];

        yield 'subdomain' => ['aula.campus.es', true];

        yield 'apex of a wildcard' => ['campus.es', false];

        yield 'suffix without a dot' => ['evilcampus.es', false];

        yield 'with scheme in the variable' => ['intranet.uni.es', true];

        yield 'other' => ['evil.example.org', false];
    }

    private function service(array $domains, string $extra = ''): FrameAncestorsService
    {
        $provider = new class($domains) implements FrameAncestorsProviderInterface {
            public function __construct(private readonly array $domains) {}

            public function frameAncestors(): array
            {
                return $this->domains;
            }
        };

        return new FrameAncestorsService([$provider], $extra);
    }
}
