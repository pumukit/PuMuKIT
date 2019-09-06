<?php

namespace Pumukit\BasePlayerBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;

class IntroService
{
    private $intro;

    public function __construct($intro)
    {
        $this->intro = $intro;
    }

    public function getVideoIntroduction(MultimediaObject $multimediaObject, bool $activateIntro = true): ?string
    {
        if (!$activateIntro) {
            return null;
        }

        $urlIntroduction = $multimediaObject->getIntroductionVideo();
        if ($urlIntroduction && filter_var($urlIntroduction, FILTER_VALIDATE_URL)) {
            return $urlIntroduction;
        }

        if (!$this->intro) {
            return null;
        }

        return $this->intro;
    }
}
