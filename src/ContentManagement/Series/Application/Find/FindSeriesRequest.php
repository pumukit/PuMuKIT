<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Find;

final class FindSeriesRequest
{
    public function __construct(public readonly string $id)
    {
        FindSeriesValidator::validate($this);
    }
}
