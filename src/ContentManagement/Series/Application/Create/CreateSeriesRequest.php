<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Create;

final class CreateSeriesRequest
{
    public function __construct(
        public readonly string $ownerId,
        public readonly ?array $title = null
    ) {
        CreateSeriesValidator::validate($this);
    }
}
