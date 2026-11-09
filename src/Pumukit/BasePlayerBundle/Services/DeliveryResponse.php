<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

final readonly class DeliveryResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $status,
        public array $headers = [],
        public string $body = '',
        public string $error = '',
    ) {}

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
