<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Domain\Exception;

final class TagAlreadyExistsException extends \RuntimeException
{
    public static function withCod(string $cod): self
    {
        return new self(sprintf('Tag with cod "%s" already exists', $cod));
    }
}
