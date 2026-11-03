<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

final class MicrositeService
{
    public const ATTRIBUTE = 'microsite';

    public function __construct(private readonly array $microsites = []) {}

    public function byHost(string $host): ?array
    {
        foreach ($this->microsites as $name => $microsite) {
            foreach ($microsite['hosts'] as $micrositeHost) {
                if (0 === strcasecmp($host, $micrositeHost)) {
                    return ['name' => (string) $name] + $microsite;
                }
            }
        }

        return null;
    }
}
