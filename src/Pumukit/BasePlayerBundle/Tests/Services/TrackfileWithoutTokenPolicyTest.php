<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Tests\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Pumukit\BasePlayerBundle\Services\TrackfileWithoutTokenPolicy;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(TrackfileWithoutTokenPolicy::class)]
final class TrackfileWithoutTokenPolicyTest extends TestCase
{
    private const SERIES_ID = '5f9d88b9c5e1a2b3c4d5e6f7';

    public function testSeriesInListIsAllowed(): void
    {
        $policy = new TrackfileWithoutTokenPolicy([self::SERIES_ID], []);

        self::assertSame('series', $policy->allowedReason($this->multimediaObject(self::SERIES_ID), new Request()));
        self::assertNull($policy->allowedReason($this->multimediaObject('5f9d88b9c5e1a2b3c4d5e6f8'), new Request()));
    }

    public function testExactRefererHostIsAllowed(): void
    {
        $policy = new TrackfileWithoutTokenPolicy([], ['example.org']);

        self::assertSame('referer', $policy->allowedReason($this->multimediaObject(self::SERIES_ID), $this->requestFrom('https://example.org/curso?x=1')));
    }

    public function testRefererSubdomainIsAllowed(): void
    {
        $policy = new TrackfileWithoutTokenPolicy([], ['example.org']);

        self::assertSame('referer', $policy->allowedReason($this->multimediaObject(self::SERIES_ID), $this->requestFrom('https://campus.example.org:8443/a')));
    }

    public function testLookalikeHostIsRejected(): void
    {
        $policy = new TrackfileWithoutTokenPolicy([], ['example.org']);

        foreach (['https://example.org.attacker.example/example.org', 'https://notexample.org/', 'https://attacker.example/?r=example.org', 'https://attacker.example/campus.example.org'] as $referer) {
            self::assertNull($policy->allowedReason($this->multimediaObject(self::SERIES_ID), $this->requestFrom($referer)), $referer);
        }
    }

    public function testRefererHostCaseInsensitive(): void
    {
        $policy = new TrackfileWithoutTokenPolicy([], ['example.org']);

        self::assertSame('referer', $policy->allowedReason($this->multimediaObject(self::SERIES_ID), $this->requestFrom('https://CAMPUS.Example.ORG/')));
    }

    public function testMissingOrMalformedRefererIsRejected(): void
    {
        $policy = new TrackfileWithoutTokenPolicy([], ['example.org']);
        $multimediaObject = $this->multimediaObject(self::SERIES_ID);

        self::assertNull($policy->allowedReason($multimediaObject, new Request()));
        self::assertNull($policy->allowedReason($multimediaObject, $this->requestFrom('')));
        self::assertNull($policy->allowedReason($multimediaObject, $this->requestFrom('example.org')));
        self::assertNull($policy->allowedReason($multimediaObject, $this->requestFrom('http:///example.org')));
    }

    public function testEmptyListsAllowNothing(): void
    {
        $policy = new TrackfileWithoutTokenPolicy([], []);

        self::assertNull($policy->allowedReason($this->multimediaObject(self::SERIES_ID), $this->requestFrom('https://example.org/')));
    }

    public function testOrphanObjectWithoutSeriesOnlyByReferer(): void
    {
        $policy = new TrackfileWithoutTokenPolicy([self::SERIES_ID], ['example.org']);
        $orphan = new MultimediaObject();

        self::assertNull($policy->seriesId($orphan));
        self::assertNull($policy->allowedReason($orphan, new Request()));
        self::assertSame('referer', $policy->allowedReason($orphan, $this->requestFrom('https://example.org/')));
    }

    private function multimediaObject(string $seriesId): MultimediaObject
    {
        $series = new Series();
        $property = new \ReflectionProperty(Series::class, 'id');
        $property->setValue($series, $seriesId);
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setSeries($series);

        return $multimediaObject;
    }

    private function requestFrom(string $referer): Request
    {
        $request = new Request();
        $request->headers->set('referer', $referer);

        return $request;
    }
}
