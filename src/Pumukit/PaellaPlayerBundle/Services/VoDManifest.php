<?php

declare(strict_types=1);

namespace Pumukit\PaellaPlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;

class VoDManifest
{
    protected $metadataManifest;
    protected $streamsManifest;
    protected $frameListManifest;
    protected $captionsManifest;
    protected $customManifest;
    protected $trimmingManifest;

    public function __construct(
        MetadataManifest $metadataManifest,
        StreamsManifest $streamsManifest,
        FrameListManifest $frameListManifest,
        CaptionsManifest $captionsManifest,
        CustomManifest $customManifest,
        TrimmingManifest $trimmingManifest
    ) {
        $this->metadataManifest = $metadataManifest;
        $this->streamsManifest = $streamsManifest;
        $this->frameListManifest = $frameListManifest;
        $this->captionsManifest = $captionsManifest;
        $this->customManifest = $customManifest;
        $this->trimmingManifest = $trimmingManifest;
    }

    public function create(MultimediaObject $multimediaObject, ?string $trackId, string $referer): array
    {
        $data = [];
        $data['metadata'] = $this->metadataManifest->create($multimediaObject);
        $data = $this->customManifest->completeManifestData($multimediaObject, $data);
        $data['streams'] = $this->streamsManifest->createStreamsForVoD($multimediaObject, $trackId, $referer)['streams'];
        $data['frameList'] = $this->frameListManifest->create($multimediaObject);
        $data['captions'] = $this->captionsManifest->create($multimediaObject);
        $trimmingData = $this->trimmingManifest->create($multimediaObject);
        if (!empty($trimmingData)) {
            $data['trimming'] = $trimmingData;
        }

        return $data;
    }
}
