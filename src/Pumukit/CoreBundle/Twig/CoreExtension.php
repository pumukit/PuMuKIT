<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CoreExtension extends AbstractExtension
{
    protected $kernelBundles;

    public function __construct(array $kernelBundles)
    {
        $this->kernelBundles = $kernelBundles;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('bundle_enabled', [$this, 'isBundleEnabled']),
        ];
    }

    public function isBundleEnabled($bundle): bool
    {
        return array_key_exists(
            $bundle,
            $this->kernelBundles
        );
    }
}
