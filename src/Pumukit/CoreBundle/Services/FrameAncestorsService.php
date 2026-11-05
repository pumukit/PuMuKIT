<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class FrameAncestorsService
{
    private const HOST = '/^(\*\.)?[a-z0-9-]+(\.[a-z0-9-]+)*(:\d+)?$/i';

    private ?array $sources = null;
    private array $untranslatable = [];

    public function __construct(
        #[AutowireIterator('pumukit.frame_ancestors_provider')]
        private readonly iterable $providers,
        private readonly ?string $extra = null,
    ) {}

    public function sources(): array
    {
        if (null !== $this->sources) {
            return $this->sources;
        }

        $sources = preg_split('/\s+/', trim((string) $this->extra), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($this->providers as $provider) {
            foreach ($provider->frameAncestors() as $pattern) {
                $source = $this->translate($pattern);
                if (null === $source) {
                    $this->untranslatable[] = $pattern;

                    continue;
                }
                $sources[] = $source;
            }
        }

        return $this->sources = array_values(array_unique($sources));
    }

    public function untranslatable(): array
    {
        $this->sources();

        return $this->untranslatable;
    }

    public function policy(): string
    {
        $sources = $this->sources();

        return in_array('*', $sources, true) ? 'frame-ancestors *' : trim("frame-ancestors 'self' ".implode(' ', $sources));
    }

    public function allowsAnyHost(): bool
    {
        return in_array('*', $this->sources(), true);
    }

    public function allows(string $host): bool
    {
        $host = strtolower($host);
        foreach ($this->sources() as $source) {
            if ('*' === $source) {
                return true;
            }

            $allowed = strtolower((string) (parse_url(str_contains($source, '://') ? $source : 'https://'.$source, PHP_URL_HOST) ?: $source));
            if ($allowed === $host || (str_starts_with($source, '*.') && str_ends_with($host, substr(strtolower($source), 1)))) {
                return true;
            }
        }

        return false;
    }

    private function translate(string $pattern): ?string
    {
        $pattern = trim($pattern);
        if ('*' === $pattern) {
            return '*';
        }

        $source = preg_replace('/\$$/', '', preg_replace('/^\^/', '', $pattern));
        $source = preg_replace('/^\.\*\\\?\./', '*.', $source);
        $source = str_replace('\.', '.', $source);

        return 1 === preg_match(self::HOST, $source) ? $source : null;
    }
}
