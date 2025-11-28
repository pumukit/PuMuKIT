<?php

declare(strict_types=1);

namespace App\Series\Application\ViewSeriesOwners;

final class ViewSeriesOwnersRequest
{
    public function __construct(
        public readonly string $seriesId
    ) {
        ViewSeriesOwnersValidator::validate($this);
    }
}
