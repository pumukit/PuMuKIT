<?php

declare(strict_types=1);

namespace App\Taxonomy\Domain\Exception;

use RuntimeException;

final class TagNotFoundException extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Tag with id "%s" not found', $id));
    }

    public static function withCod(string $cod): self
    {
        return new self(sprintf('Tag with cod "%s" not found', $cod));
    }
}

