<?php

namespace Pumukit\CoreBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

class CoreExtension extends \Twig_Extension
{
    protected $container;

    /**
     * Constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get name.
     */
    public function getName()
    {
        return 'pumukit_core_extension';
    }

    /**
     * Get functions.
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('bundle_enabled', array($this, 'isBundleEnabled')),
        );
    }

    /**
     * Is bundle enabled.
     *
     * @param string $bundle
     *
     * @return bool
     */
    public function isBundleEnabled($bundle)
    {
        dump($this->container->getParameter('kernel.bundles'));
        return array_key_exists(
            $bundle,
            $this->container->getParameter('kernel.bundles')
        );
    }
}
