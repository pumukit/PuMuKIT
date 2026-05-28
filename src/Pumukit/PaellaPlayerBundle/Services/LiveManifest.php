<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;

class LiveManifest
{
    protected $metadataManifest;
    protected $streamsManifest;
    protected $frameListManifest;
    protected $captionsManifest;

    public function __construct(
        MetadataManifest $metadataManifest,
        StreamsManifest $streamsManifest,
        FrameListManifest $frameListManifest,
        CaptionsManifest $captionsManifest
    ) {
        $this->metadataManifest = $metadataManifest;
        $this->streamsManifest = $streamsManifest;
        $this->frameListManifest = $frameListManifest;
        $this->captionsManifest = $captionsManifest;
    }

    public function create(MultimediaObject $multimediaObject): array
    {
        $data = [];
        $data['metadata'] = $this->metadataManifest->create($multimediaObject);
        $data['metadata']['duration'] = 1;
        $data['metadata']['isStreaming'] = true;
        $data['streams'] = $this->streamsManifest->createStreamsForLive($multimediaObject)['streams'];
        $data['frameList'] = $this->frameListManifest->create($multimediaObject);
        $data['captions'] = $this->captionsManifest->create($multimediaObject);

        return $data;
    }
}
