<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\View;

use Pumukit\SchemaBundle\Document\Tag;

final class ViewTagResponse
{
    public function __construct(
        public Tag $tag
    ) {}
}
