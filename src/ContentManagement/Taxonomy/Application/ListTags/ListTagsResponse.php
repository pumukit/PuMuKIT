<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\ListTags;

final class ListTagsResponse
{
    public function __construct(
        public array $tags
    ) {}
}
