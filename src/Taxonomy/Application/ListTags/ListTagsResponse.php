<?php

declare(strict_types=1);

namespace App\Taxonomy\Application\ListTags;

final readonly class ListTagsResponse
{
    public function __construct(
        public array $tags
    ) {}
}
