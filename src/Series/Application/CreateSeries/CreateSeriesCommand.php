<?php

namespace App\Series\Application\CreateSeries;

final class CreateSeriesCommand
{
    public function __construct(
        public readonly string $ownerId,
        public readonly ?array $title = null
    ) {}
}
