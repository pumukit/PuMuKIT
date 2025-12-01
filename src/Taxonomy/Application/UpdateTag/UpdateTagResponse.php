<?php

declare(strict_types=1);

namespace App\Taxonomy\Application\UpdateTag;

use Pumukit\SchemaBundle\Document\Tag;

final readonly class UpdateTagResponse
{
    public function __construct(
        public Tag $tag
    ) {}
}

