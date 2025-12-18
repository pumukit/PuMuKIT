<?php

declare(strict_types=1);

namespace App\Shared\Domain\Criteria;

final class Order
{
    public function __construct(
        private string $orderBy,
        private string $orderType
    ) {}

    public function orderBy(): string { return $this->orderBy; }
    public function orderType(): string { return $this->orderType; }
}
