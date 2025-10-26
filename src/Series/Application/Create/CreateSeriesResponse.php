<?php

namespace App\Series\Application\CreateSeries;

final class CreateSeriesResponse
{
    public function __construct(
        public readonly string $id,
        public readonly array $title,
        public readonly string $ownerId
    ) {}
}
