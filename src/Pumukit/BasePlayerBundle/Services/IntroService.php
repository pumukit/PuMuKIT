<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\HeadAndTailService;

class IntroService
{
    private HeadAndTailService $headAndTailService;

    public function __construct(HeadAndTailService $headAndTailService)
    {
        $this->headAndTailService = $headAndTailService;
    }

    public function getVideoIntroduction(MultimediaObject $multimediaObject, $activateIntroFromRequest = true): ?MediaInterface
    {
        $activateIntroFromRequest = false === filter_var($activateIntroFromRequest, FILTER_VALIDATE_BOOLEAN);

        if (!$activateIntroFromRequest) {
            return null;
        }

        $videoIntro = $this->headAndTailService->getHeadToPlay($multimediaObject);
        if (!$videoIntro) {
            return null;
        }
        $videoTrack = $this->headAndTailService->getDisplayTrackFromMultimediaObjectId($videoIntro);

        if ($videoTrack instanceof MediaInterface) {
            return $videoTrack;
        }

        return null;
    }

    public function getVideoTail(MultimediaObject $multimediaObject): ?MediaInterface
    {
        $videoTail = $this->headAndTailService->getTailToPlay($multimediaObject);
        if (!$videoTail) {
            return null;
        }
        $videoTrack = $this->headAndTailService->getDisplayTrackFromMultimediaObjectId($videoTail);

        if ($videoTrack instanceof MediaInterface) {
            return $videoTrack;
        }

        return null;
    }
}
