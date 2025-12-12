<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Domain\Exception;

final class PersonAlreadyExistsException extends \Exception
{
    public static function withEmail(string $email): self
    {
        return new self(sprintf('Person with email "%s" already exists', $email));
    }
}
