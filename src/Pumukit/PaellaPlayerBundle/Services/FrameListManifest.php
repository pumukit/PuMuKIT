<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;

class FrameListManifest
{
    public function create(MultimediaObject $multimediaObject): array
    {
        $images = [];
        foreach ($multimediaObject->getEmbeddedSegments() ?? [] as $segment) {
            $time = (int) ($segment->getTime() / 1000);
            $images[] = [
                'id' => 'frame_'.$time,
                'mimetype' => 'image/jpeg',
                'time' => $time,
                'url' => $segment->getPreview(),
                'thumb' => $segment->getPreview(),
                'caption' => $segment->getText(),
            ];
        }

        return $images;
    }
}
