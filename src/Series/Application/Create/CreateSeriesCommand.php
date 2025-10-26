<?php

namespace App\Series\Application\Create;

final class CreateSeriesCommand
{
    public function __construct(
        public readonly string $ownerId,
        public readonly ?array $title = null
    ) {}
}
