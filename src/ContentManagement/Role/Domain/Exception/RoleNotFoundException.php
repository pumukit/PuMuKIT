<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Domain\Exception;

final class RoleNotFoundException extends \Exception
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Role with id "%s" not found', $id));
    }

    public static function withCod(string $cod): self
    {
        return new self(sprintf('Role with cod "%s" not found', $cod));
    }
}
