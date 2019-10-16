<?php

namespace Pumukit\CoreBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CoreExtension extends AbstractExtension
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('bundle_enabled', [$this, 'isBundleEnabled']),
        ];
    }

    public function isBundleEnabled(string $bundle): bool
    {
        return array_key_exists(
            $bundle,
            $this->container->getParameter('kernel.bundles')
        );
    }
}
