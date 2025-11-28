<?php

declare(strict_types=1);

namespace App\Series\Application\ViewSeriesOwners;

final class ViewSeriesOwnersResponse
{
    public function __construct(
        public readonly array $owners
    ) {}
}

