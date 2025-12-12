<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Domain\Exception;

final class PersonNotFoundException extends \Exception
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Person with id "%s" not found', $id));
    }

    public static function withEmail(string $email): self
    {
        return new self(sprintf('Person with email "%s" not found', $email));
    }
}
