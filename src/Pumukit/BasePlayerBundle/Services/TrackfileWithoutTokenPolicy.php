<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Request;

class TrackfileWithoutTokenPolicy
{
    public const REASON_SERIES = 'series';
    public const REASON_REFERER = 'referer';

    public function __construct(
        private readonly array $series = [],
        private readonly array $refererHosts = []
    ) {}

    public function allowedReason(MultimediaObject $multimediaObject, Request $request): ?string
    {
        $seriesId = $this->seriesId($multimediaObject);
        if (null !== $seriesId && \in_array($seriesId, $this->series, true)) {
            return self::REASON_SERIES;
        }

        $host = $this->refererHost($request);
        if (null === $host) {
            return null;
        }

        foreach ($this->refererHosts as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return self::REASON_REFERER;
            }
        }

        return null;
    }

    public function seriesId(MultimediaObject $multimediaObject): ?string
    {
        $series = $multimediaObject->getSeries();

        return null === $series ? null : (string) $series->getId();
    }

    public function refererHost(Request $request): ?string
    {
        $referer = $request->headers->get('referer');
        if (null === $referer || '' === $referer) {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);

        return \is_string($host) && '' !== $host ? strtolower($host) : null;
    }
}
