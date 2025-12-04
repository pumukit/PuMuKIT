<?php

declare(strict_types=1);

namespace App\Taxonomy\Application\CreateTag;

use Pumukit\SchemaBundle\Document\Tag;

final readonly class CreateTagResponse
{
    public function __construct(
        public Tag $tag
    ) {}
}
