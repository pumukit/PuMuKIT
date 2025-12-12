<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Domain\Exception;

final class RoleAlreadyExistsException extends \Exception
{
    public static function withCod(string $cod): self
    {
        return new self(sprintf('Role with cod "%s" already exists', $cod));
    }
}
