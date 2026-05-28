<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Services;

use Pumukit\BaseLivePlayerBundle\Services\LiveService;
use Pumukit\BasePlayerBundle\Services\TrackUrlService;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\PicService;
use Symfony\Component\Mime\MimeTypes;

class StreamsManifest
{
    private $picService;
    private $trackUrlService;
    private $liveService;
    private $requestContextScheme;
    private $requestContextHost;
    private $refererContext;

    public function __construct(
        PicService $picService,
        TrackUrlService $trackUrlService,
        LiveService $liveService,
        string $requestContextScheme,
        string $requestContextHost
    ) {
        $this->picService = $picService;
        $this->trackUrlService = $trackUrlService;
        $this->liveService = $liveService;
        $this->requestContextScheme = $requestContextScheme;
        $this->requestContextHost = $requestContextHost;
    }

    public function createStreamsForVoD(MultimediaObject $multimediaObject, ?string $trackId, string $referer): array
    {
        // This sentence set the domain to create the absolute url of the track
        $this->refererContext = $referer;

        $data = [];
        $data['streams'] = [];

        if (!$multimediaObject->isMultistream()) {
            $track = $multimediaObject->getTrackById($trackId) ?? $this->searchPriorityTrack($multimediaObject);
            if ($track) {
                $dataStream = $this->buildDataStream([$track]);
                $dataStream['content'] = 'presenter';
                $dataStream['audioTag'] = $track->language();

                $pic = $this->getPreview($multimediaObject);

                $dataStream['preview'] = $pic;
                $dataStream['language'] = $track->language();
                $data['streams'][] = $dataStream;
            }

            return $data;
        }

        $tracks = $this->getMmobjTracks($multimediaObject, $trackId);

        // Camera tracks
        if ($tracks['display']) {
            $dataStream = $this->buildDataStream($tracks['display']);
            $pic = $this->getPreview($multimediaObject);
            $dataStream['preview'] = $pic;
            $dataStream['language'] = $tracks['display'][0]->language();
            $dataStream['content'] = 'presenter';
            $dataStream['audioTag'] = $tracks['display'][0]->language();
            $data['streams'][] = $dataStream;
        }

        // Presentation tracks
        if ($tracks['presentation']) {
            $dataStream = $this->buildDataStream($tracks['presentation']);
            $dataStream['language'] = $tracks['presentation'][0]->language();
            $dataStream['content'] = 'presentation';
            $data['streams'][] = $dataStream;
        }

        return $data;
    }

    public function createStreamsForLive(MultimediaObject $multimediaObject): array
    {
        $data = [];
        $url = '';
        $live = $multimediaObject->getEmbeddedEvent()->getLive();

        if ($live && empty($multimediaObject->getEmbeddedEvent()->getUrl())) {
            $url = $this->liveService->generateHlsUrl($live);
        } elseif ($multimediaObject->getEmbeddedEvent()->getUrl() && (false !== strpos($multimediaObject->getEmbeddedEvent()->getUrl(), 'rtmp://')
                || false !== strpos($multimediaObject->getEmbeddedEvent()->getUrl(), 'rtmpt://'))) {
            $url = $this->liveService->genHlsUrlEvent($multimediaObject->getEmbeddedEvent()->getUrl());
        }

        if (false === strpos($url, 'https')) {
            $url = 'https:'.$url;
        }

        $dataStream = $this->buildDataLive([$url], 'video/mp4');
        $dataStream['preview'] = '';
        $dataStream['content'] = 'presenter';
        $dataStream['role'] = 'mainAudio';
        $data['streams'][] = $dataStream;

        return $data;
    }

    public function buildDataLive(array $urls, string $mimeType): array
    {
        $dataStream = [];
        $sources = [];
        foreach ($urls as $url) {
            $src = $url;
            $dataStreamTrack = [
                'src' => $src,
                'mimetype' => $mimeType,
            ];

            //            if ($track->getWidth() && $track->getHeight()) {
            //                $dataStreamTrack['res'] = ['w' => $track->getWidth(), 'h' => $track->getHeight()];
            //            }

            if (!isset($sources['hlsLive'])) {
                $sources['hlsLive'] = [];
            }
            $sources['hlsLive'][] = $dataStreamTrack;
        }
        $dataStream['sources'] = $sources;

        return $dataStream;
    }

    public function searchPriorityTrack(MultimediaObject $multimediaObject): Track
    {
        $displayTracks = $multimediaObject->getTracksWithTag('display');
        if (count($displayTracks) >= 2) {
            foreach ($displayTracks as $track) {
                if (str_ends_with($track->storage()->path()->path(), '.m3u8')) {
                    return $track;
                }
            }
        }

        return $multimediaObject->getDisplayTrack();
    }

    private function getMmobjTracks(MultimediaObject $multimediaObject, ?string $trackId): array
    {
        $tracks = [
            'display' => [],
            'presentation' => [],
            'sbs' => [],
        ];
        $availableCodecs = ['h264', 'vp8', 'vp9'];

        if ($trackId) {
            $track = $multimediaObject->getTrackById($trackId);
            if ($track) {
                if ($track->containsAnyTag(['display', 'presenter/delivery', 'presentation/delivery']) && in_array($track->metadata()->codecName(), $availableCodecs)) {
                    $tracks['display'][] = $track;
                }
                if ($track->metadata()->isOnlyAudio()) {
                    $tracks['display'][] = $track;
                }

                return $tracks;
            }
        }

        $presenterTracks = $multimediaObject->getFilteredTracksWithTags(['presenter/delivery']);
        $presentationTracks = $multimediaObject->getFilteredTracksWithTags(['presentation/delivery']);
        $sbsTrack = $multimediaObject->getTrackWithTag('sbs');

        foreach ($presenterTracks as $track) {
            if (in_array($track->metadata()->codecName(), $availableCodecs)) {
                $tracks['display'][] = $track;
            }
        }
        foreach ($presentationTracks as $track) {
            if (in_array($track->metadata()->codecName(), $availableCodecs)) {
                $tracks['presentation'][] = $track;
            }
        }

        if ($sbsTrack && in_array($sbsTrack->metadata()->codecName(), $availableCodecs)) {
            $tracks['sbs'][] = $sbsTrack;
        }

        if (!$tracks['display'] && !$tracks['presentation']) {
            $track = $multimediaObject->getDisplayTrack();
            if ($track && in_array($track->metadata()->codecName(), $availableCodecs)) {
                $tracks['display'][] = $track;
            }
        }

        return $tracks;
    }

    private function buildDataStream(array $tracks): array
    {
        $dataStream = [];
        $sources = [];
        foreach ($tracks as $track) {
            //            $mimeType = $track->metadata()->mimetype();
            $mimeTypes = new MimeTypes();
            $mimeType = $mimeTypes->guessMimeType($track->storage()->path()->path());
            if ('text/plain' === $mimeType && str_ends_with($track->storage()->path()->path(), '.m3u8')) {
                $mimeType = 'video/mp4';
            }
            $src = $this->getAbsoluteUrl($this->trackUrlService->generateTrackFileUrl($track));

            $dataStreamTrack = [
                'src' => $src,
                'mimetype' => $mimeType,
            ];

            // If pumukit doesn't know the resolution, paella can guess it.
            if ($track->metadata()->width() && $track->metadata()->height()) {
                $dataStreamTrack['res'] = ['w' => $track->metadata()->width(), 'h' => $track->metadata()->height()];
            }

            $format = explode('/', $mimeType)[1] ?? 'mp4';

            if (in_array($format, ['mpeg', 'x-m4a']) && $track->metadata()->isOnlyAudio()) {
                $format = 'audio';
            } elseif (str_ends_with($track->storage()->path()->path(), '.m3u8') && 'video/mp4' === $mimeType) {
                $format = 'hls';
            }

            if (!isset($sources[$format])) {
                $sources[$format] = [];
            }
            $sources[$format][] = $dataStreamTrack;
        }
        $dataStream['sources'] = $sources;

        return $dataStream;
    }

    private function getAbsoluteUrl(string $url): string
    {
        if (false !== strpos($url, '://') || 0 === strpos($url, '//')) {
            return $url;
        }

        return $this->requestContextScheme.'://'.$this->refererContext.$url;
    }

    private function getPreview(MultimediaObject $multimediaObject)
    {
        $image = $this->picService->getPosterUrl($multimediaObject, true);
        if (!$image) {
            $image = $this->picService->getFirstUrlPic($multimediaObject, true, true);
        }

        return $image;
    }
}
