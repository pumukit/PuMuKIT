<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\DeleteTag;

final readonly class DeleteTagRequest
{
    public function __construct(
        public string $id
    ) {}
}
