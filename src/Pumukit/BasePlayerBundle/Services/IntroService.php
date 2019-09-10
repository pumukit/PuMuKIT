<?php

namespace Pumukit\BasePlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;

class IntroService
{
    private $globalUrlIntroduction;

    public function __construct($globalUrlIntroduction)
    {
        $this->globalUrlIntroduction = $globalUrlIntroduction;
    }

    public function getVideoIntroduction(MultimediaObject $multimediaObject, $activateIntroFromRequest = true): ?string
    {
        $activateIntroFromRequest = false === filter_var($activateIntroFromRequest, FILTER_VALIDATE_BOOLEAN);

        if (!$activateIntroFromRequest) {
            return null;
        }

        $urlIntroduction = $multimediaObject->getIntroductionVideo();

        if ($urlIntroduction && filter_var($urlIntroduction, FILTER_VALIDATE_URL)) {
            return $urlIntroduction;
        }

        if (!$this->globalUrlIntroduction) {
            return null;
        }

        return $this->globalUrlIntroduction;
    }
}
