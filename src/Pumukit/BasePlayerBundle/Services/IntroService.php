<?php

namespace Pumukit\BasePlayerBundle\Services;

/**
 * Wrapper around the pumukit2.intro parameter.
 */
class IntroService
{
    private $intro = null;

    public function __construct($intro)
    {
        $this->intro = $intro;
    }

    /**
     * Return the intro url if introParameter is null or 'true'.
     *
     * @param mixed $introParameter request parameter = null
     *
     * @return string|null
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
}
