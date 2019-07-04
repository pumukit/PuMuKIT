<?php

namespace Pumukit\BasePlayerBundle\Services;

/**
 * Wrapper around the pumukit.intro parameter.
 */
class IntroService
{
    private $intro;

    public function __construct($intro)
    {
        $this->intro = $intro;
    }

    /**
     * Returns the intro url if introParameter is null or 'true'.
     *
     * @param mixed $introParameter request parameter null|'false'|'true'
     *
     * @return null|string
     */
    public function getIntro($introParameter = null)
    {
        $hasIntro = (bool) $this->intro;

        $showIntro = true;
        if (null !== $introParameter && false === filter_var($introParameter, FILTER_VALIDATE_BOOLEAN)) {
            $showIntro = false;
        }

        if ($hasIntro && $showIntro) {
            return $this->intro;
        }

        return false;
    }

    /**
     * Returns the intro url if introParameter is null or 'true' and not exist an introProperty.
     * Returns the intro property if it is a string and introParameter is null or 'true'.
     *
     * @param mixed      $introPropoerty multimedia object property null|false|'url'
     * @param mixed      $introParameter request parameter null|string('false'|'true')
     * @param null|mixed $introProperty
     *
     * @return null|bool
     */
    public function getIntroForMultimediaObject($introProperty = null, $introParameter = null)
    {
        $showIntro = true;
        if (null !== $introParameter && false === filter_var($introParameter, FILTER_VALIDATE_BOOLEAN)) {
            $showIntro = false;
        }

        $hasIntro = (bool) $this->intro;
        if ($hasIntro && $showIntro && null === $introProperty) {
            return $this->intro;
        }

        $hasCustomIntro = (bool) $introProperty;
        if ($hasCustomIntro && $showIntro) {
            return $introProperty;
        }

        return false;
    }
}
