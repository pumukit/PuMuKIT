<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\ViewTag;

use Pumukit\SchemaBundle\Document\Tag;

final class ViewTagResponse
{
    public function __construct(
        public Tag $tag
    ) {}
}
