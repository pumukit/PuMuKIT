<?php

namespace App\Series\Domain\Exception;

use DomainException;

final class SeriesNotFoundException extends DomainException
{
    public function __construct(string $seriesId)
    {
        parent::__construct("Series with id '{$seriesId}' not found");
    }
}
