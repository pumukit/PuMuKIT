<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\ListTags;

final readonly class ListTagsRequest
{
    public function __construct(
        public ?string $parentId = null,
        public bool $onlyRoots = false
    ) {}
}
