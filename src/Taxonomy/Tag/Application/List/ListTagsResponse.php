<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\List;

final class ListTagsResponse
{
    public function __construct(
        public array $tags
    ) {}
}
