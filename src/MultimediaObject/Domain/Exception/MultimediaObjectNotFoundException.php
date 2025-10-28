<?php

declare(strict_types=1);

namespace App\MultimediaObject\Domain\Exception;

final class MultimediaObjectNotFoundException extends \DomainException
{
    public function __construct(string $multimediaObjectId)
    {
        parent::__construct(sprintf("Multimedia Object with id '%s' not found", $multimediaObjectId));
    }
}
