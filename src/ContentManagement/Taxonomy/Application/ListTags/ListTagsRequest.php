<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\ListTags;

final class ListTagsRequest
{
    public function __construct(
        public ?string $parentId = null,
        public bool $onlyRoots = false
    ) {}
}
