<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pumukit\StatsBundle\Services\VisitorAnonymizer;

/**
 * @internal
 */
#[CoversClass(VisitorAnonymizer::class)]
final class VisitorAnonymizerTest extends TestCase
{
    #[DataProvider('addresses')]
    public function testTheIpIsTruncated(string $ip, ?string $expected): void
    {
        $this->assertSame($expected, (new VisitorAnonymizer('secret'))->anonymizeIp($ip));
    }

    public static function addresses(): iterable
    {
        yield 'IPv4 keeps the /24 network' => ['203.0.113.42', '203.0.113.0'];

        yield 'IPv6 keeps the /48 network' => ['2001:db8:abcd:12:3456:789a:bcde:f012', '2001:db8:abcd::'];

        yield 'invalid address' => ['not-an-ip', null];

        yield 'empty address' => ['', null];
    }

    public function testTheVisitorChangesEveryDayAndDoesNotContainTheIp(): void
    {
        $anonymizer = new VisitorAnonymizer('secret');
        $monday = $anonymizer->visitor('203.0.113.42', 'Firefox', new \DateTimeImmutable('2026-10-26 10:00'));

        $this->assertSame($monday, $anonymizer->visitor('203.0.113.42', 'Firefox', new \DateTimeImmutable('2026-10-26 23:00')));
        $this->assertNotSame($monday, $anonymizer->visitor('203.0.113.42', 'Firefox', new \DateTimeImmutable('2026-10-27 10:00')));
        $this->assertNotSame($monday, (new VisitorAnonymizer('other'))->visitor('203.0.113.42', 'Firefox', new \DateTimeImmutable('2026-10-26 10:00')));
        $this->assertStringNotContainsString('203.0.113', $monday);
    }
}
