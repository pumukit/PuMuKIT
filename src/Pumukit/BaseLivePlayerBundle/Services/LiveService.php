<?php

namespace Pumukit\BaseLivePlayerBundle\Services;

use Pumukit\SchemaBundle\Document\Live;

class LiveService
{
    /**
     * Generate HLS URL from RTMP url.
     * Original twig template:
     *    {{ live.url|replace({'rtmp://':'http://', 'rtmpt://': 'http://'}) }}/{{ live.sourcename }}/playlist.m3u8.
     *
     * @param Live $live
     *
     * @return string
     */
    public function generateHlsUrl(Live $live)
    {
        switch ($live->getLiveType()) {
            case Live::LIVE_TYPE_AMS:
                $hls = sprintf(
                    '%s/%s/%s.m3u8',
                    str_replace(['rtmp://', 'rtmpt://'], '//', $live->getUrl()),
                    $live->getSourceName(),
                    $live->getSourceName()
                );

                break;
            default:
                $hls = sprintf(
                    '%s/%s/playlist.m3u8',
                    str_replace(['rtmp://', 'rtmpt://'], '//', $live->getUrl()),
                    $live->getSourceName()
                );

                break;
        }

        return $hls;
    }
}
