<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\Update;

use Pumukit\SchemaBundle\Document\Tag;

final class UpdateTagResponse
{
    public function __construct(
        public Tag $tag
    ) {}
}
