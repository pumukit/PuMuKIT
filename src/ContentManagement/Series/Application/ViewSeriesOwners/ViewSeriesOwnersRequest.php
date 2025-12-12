<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\ViewSeriesOwners;

final class ViewSeriesOwnersRequest
{
    public function __construct(
        public string $seriesId
    ) {
        ViewSeriesOwnersValidator::validate($this);
    }
}
