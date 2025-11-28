<?php

declare(strict_types=1);

namespace App\Series\Application\Delete;

final class DeleteSeriesRequest
{
    public function __construct(
        public readonly string $id
    ) {
        DeleteSeriesValidator::validate($this);
    }
}
