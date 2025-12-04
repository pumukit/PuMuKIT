<?php

declare(strict_types=1);

namespace App\MultimediaObject\Domain\Exception;

final class MultimediaObjectNotFoundException extends \RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('MultimediaObject with ID "%s" not found', $id));
    }
}
