<?php

declare(strict_types=1);

namespace Pumukit\BaseLivePlayerBundle\Services;

use Pumukit\SchemaBundle\Document\Live;

class LiveService
{
    /**
     * Generate HLS URL from RTMP url.
     * Original twig template: {{ live.url|replace({'rtmp://':'http://', 'rtmpt://': 'http://'}) }}/{{ live.sourcename }}/playlist.m3u8.
     */
    public function generateHlsUrl(Live $live): string
    {
        $sourceName = $live->getSourceName();

        return sprintf('%s/%s/playlist.m3u8', str_replace(['rtmp://', 'rtmpt://'], '//', $live->getUrl()), $sourceName);
    }

    public function genHlsUrlEvent(string $urlEvent): string
    {
        return sprintf('%s/playlist.m3u8', str_replace(['rtmp://', 'rtmpt://'], '//', $urlEvent));
    }

    public function genHlsUrlEvent(string $urlEvent): string
    {
        return sprintf('%s/playlist.m3u8', str_replace(['rtmp://', 'rtmpt://'], '//', $urlEvent));
    }
}
