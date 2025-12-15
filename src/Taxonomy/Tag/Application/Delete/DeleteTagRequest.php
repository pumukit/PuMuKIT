<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\Delete;

final class DeleteTagRequest
{
    public function __construct(
        public string $id
    ) {}
}
