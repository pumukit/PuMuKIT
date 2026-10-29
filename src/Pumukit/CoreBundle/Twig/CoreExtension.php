<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CoreExtension extends AbstractExtension
{
    private const SAFE_URL_SCHEMES = ['http', 'https', 'mailto'];

    protected $kernelBundles;

    public function __construct(array $kernelBundles)
    {
        $this->kernelBundles = $kernelBundles;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('pumukit_spaceless', [$this, 'spaceless'], ['pre_escape' => 'html', 'is_safe' => ['html']]),
            new TwigFilter('safe_url', [$this, 'safeUrl']),
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

    public function safeUrl(?string $url): string
    {
        if (null === $url) {
            return '';
        }

        $normalized = preg_replace('/[\x00-\x20\x7F]/', '', $url);
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $normalized, $matches) && !in_array(strtolower($matches[1]), self::SAFE_URL_SCHEMES, true)) {
            return '#';
        }

        return $url;
    }

    public function isBundleEnabled($bundle): bool
    {
        return array_key_exists(
            $bundle,
            $this->kernelBundles
        );
    }
}
