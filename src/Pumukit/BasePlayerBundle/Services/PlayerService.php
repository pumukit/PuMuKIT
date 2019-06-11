<?php

namespace Pumukit\BasePlayerBundle\Services;

/**
 * Wrapper around the pumukit.intro parameter.
 */
class PlayerService
{
    private $kernelBundles = [];

    const publicControllerString = ':BasePlayer:index';
    const magicControllerString = ':BasePlayer:magic';

    public function __construct(array $kernelBundles)
    {
        $this->kernelBundles = $kernelBundles;
    }

    public function getInstalledPlayerBundle()
    {
        if (array_key_exists('PumukitPaellaPlayerBundle', $this->kernelBundles)) {
            return 'PumukitPaellaPlayerBundle';
        }

        return 'PumukitJWPlayerBundle';
    }

    public function getPublicControllerPlayer()
    {
        $bundle = $this->getInstalledPlayerBundle();

        $publicController = $bundle.self::publicControllerString;

        return $publicController;
    }

    public function getMagicControllerPlayer()
    {
        $bundle = $this->getInstalledPlayerBundle();

        $magicController = $bundle.self::magicControllerString;

        return $magicController;
    }
}
