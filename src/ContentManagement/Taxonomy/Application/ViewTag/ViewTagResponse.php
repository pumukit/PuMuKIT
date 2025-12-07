<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\ViewTag;

use Pumukit\SchemaBundle\Document\Tag;

final readonly class ViewTagResponse
{
    public function __construct(
        public Tag $tag
    ) {}
}
