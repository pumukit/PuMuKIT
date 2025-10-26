<?php

namespace App\Series\Application\Delete;

final class DeleteSeriesRequest
{
    public function __construct(
        public readonly string $id
    ) {}
}
