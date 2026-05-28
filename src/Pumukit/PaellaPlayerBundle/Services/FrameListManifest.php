<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;

class FrameListManifest
{
    private $opencastClient;

    public function __construct(?Pumukit\OpencastBundle\Services\Client $opencastClient)
    {
        $this->opencastClient = $opencastClient;
    }

    public function create(MultimediaObject $multimediaObject)
    {
        $images = $this->getFrameListFromPumukit($multimediaObject);

        if (!$images) {
            $images = $this->getFrameListFromOpencast($multimediaObject);
        }

        return $images;
    }

    private function getFrameListFromPumukit(MultimediaObject $multimediaObject): ?array
    {
        if (!method_exists($multimediaObject, 'getEmbeddedSegments')) {
            return null;
        }

        $segments = $multimediaObject->getEmbeddedSegments();
        if (!$segments) {
            return null;
        }

        $images = [];
        foreach ($segments as $segment) {
            $time = (int) ($segment->getTime() / 1000);
            $id = 'frame_'.$time;
            $mimeType = 'image/jpeg';

            $images[] = [
                'id' => $id,
                'mimetype' => $mimeType,
                'time' => $time,
                'url' => $segment->getPreview(),
                'thumb' => $segment->getPreview(),
                'caption' => $segment->getText(),
            ];
        }

        return $images;
    }

    private function getFrameListFromOpencast(MultimediaObject $multimediaObject): array
    {
        if (!$this->opencastClient) {
            return [];
        }

        $images = [];
        if ($opencastId = $multimediaObject->getProperty('opencast')) {
            $mediaPackage = null;

            try {
                $mediaPackage = $this->opencastClient->getFullMediaPackage($opencastId);
            } catch (\Exception $e) {
                // TODO: Inject logger and log a warning.
            }

            if (!isset($mediaPackage['segments']['segment'])) {
                return [];
            }

            // Fix Opencast one-result behavior
            if (isset($mediaPackage['segments']['segment']['time'])) {
                $segments = [$mediaPackage['segments']['segment']];
            } else {
                $segments = $mediaPackage['segments']['segment'];
            }

            foreach ($segments as $segment) {
                $time = (int) ($segment['time'] / 1000);
                $id = 'frame_'.$time;
                $mimeType = 'image/jpeg';

                $url = '';
                if (isset($segment['previews']['preview']['$'])) {
                    $url = $segment['previews']['preview']['$'];
                }

                $images[] = [
                    'id' => $id,
                    'mimetype' => $mimeType,
                    'time' => $time,
                    'url' => $url,
                    'thumb' => $url,
                    'caption' => $segment['text'],
                ];
            }
        }

        return $images;
    }
}
