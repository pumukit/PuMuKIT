<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\Create;

use Pumukit\SchemaBundle\Document\Tag;

final class CreateTagResponse
{
    public function __construct(
        public Tag $tag
    ) {}
}
