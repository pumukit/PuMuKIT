<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CoreExtension extends AbstractExtension
{
    protected $kernelBundles;

    public function __construct(array $kernelBundles)
    {
        $this->kernelBundles = $kernelBundles;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('pumukit_spaceless', [$this, 'spaceless'], ['pre_escape' => 'html', 'is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('bundle_enabled', [$this, 'isBundleEnabled']),
        ];
    }

    public function spaceless(?string $content): string
    {
        return trim(preg_replace('/>\s+</', '><', $content ?? ''));
    }

    public function isBundleEnabled($bundle): bool
    {
        return array_key_exists(
            $bundle,
            $this->kernelBundles
        );
    }
}
