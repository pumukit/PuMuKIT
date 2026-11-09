<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

final readonly class DeliveryCheckResult
{
    public function __construct(
        public string $name,
        public bool $passed,
        public string $detail,
    ) {}
}
