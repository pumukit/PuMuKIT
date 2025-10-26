<?php

namespace App\Series\Application\Create;

final class CreateSeriesResponse
{
    public function __construct(
        public readonly string $id,
        public readonly array $title,
        public readonly string $ownerId
    ) {}
}
