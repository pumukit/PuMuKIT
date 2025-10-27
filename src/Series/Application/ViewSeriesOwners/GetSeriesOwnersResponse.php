<?php

declare(strict_types=1);

namespace App\Series\Application\ViewSeriesOwners;

final class GetSeriesOwnersResponse
{
    public function __construct(
        public readonly array $owners
    ) {}
}

