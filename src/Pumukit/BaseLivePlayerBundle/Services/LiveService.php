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
    public function generateHlsUrl(Live $live, int $numStream = null): string
    {
        $sourceName = $live->getSourceName();
        $sourceName2 = $live->getSourceName2();

        if (null !== $numStream and 2 == $numStream) {
            if (Live::LIVE_TYPE_AMS === $live->getLiveType()) {
                $hls2 = sprintf('%s/%s/%s.m3u8', str_replace(['rtmp://', 'rtmpt://'], '//', $live->getUrl()), $sourceName2, $sourceName2);
            } else {
                $hls2 = sprintf('%s/%s/playlist.m3u8', str_replace(['rtmp://', 'rtmpt://'], '//', $live->getUrl()), $sourceName2);
            }

            return $hls2;
        }

        if (Live::LIVE_TYPE_AMS === $live->getLiveType()) {
            $hls = sprintf('%s/%s/%s.m3u8', str_replace(['rtmp://', 'rtmpt://'], '//', $live->getUrl()), $sourceName, $sourceName);
        } else {
            $hls = sprintf('%s/%s/playlist.m3u8', str_replace(['rtmp://', 'rtmpt://'], '//', $live->getUrl()), $sourceName);
        }

        return $hls;
    }

    public function genHlsUrlEvent(string $urlEvent): string
    {
        return sprintf('%s/playlist.m3u8', str_replace(['rtmp://', 'rtmpt://'], '//', $urlEvent));
    }
}
