<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\ViewSeriesOwners;

final class ViewSeriesOwnersResponse
{
    public function __construct(
        public array $owners
    ) {}
}
