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
     * Get functions.
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('bundle_enabled', [$this, 'isBundleEnabled']),
        ];
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
        return array_key_exists(
            $bundle,
            $this->container->getParameter('kernel.bundles')
        );
    }
}
