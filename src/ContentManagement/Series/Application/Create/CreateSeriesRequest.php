<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Create;

final class CreateSeriesRequest
{
    public function __construct(
        public string $ownerId,
        public ?array $title = null
    ) {
        CreateSeriesValidator::validate($this);
    }
}
