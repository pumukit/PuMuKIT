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

    public function getVideoIntroduction(MultimediaObject $multimediaObject, bool $activateIntroFromRequest = true): ?string
    {
        if (!$activateIntroFromRequest) {
            return null;
        }

        $urlIntroduction = $multimediaObject->getIntroductionVideo();

        if($activateIntroFromRequest && $this->globalUrlIntroduction && !$urlIntroduction) {
            return $this->globalUrlIntroduction;
        }

        if ($urlIntroduction && filter_var($urlIntroduction, FILTER_VALIDATE_URL)) {
            return $urlIntroduction;
        }

        if (!$this->globalUrlIntroduction) {
            return null;
        }

        return $this->globalUrlIntroduction;
    }
}
