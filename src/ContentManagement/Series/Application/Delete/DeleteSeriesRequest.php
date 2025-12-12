<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Delete;

final class DeleteSeriesRequest
{
    public function __construct(
        public string $id
    ) {
        DeleteSeriesValidator::validate($this);
    }
}
