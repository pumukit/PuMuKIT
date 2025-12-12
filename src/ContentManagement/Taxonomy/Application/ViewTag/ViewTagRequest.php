<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\ViewTag;

final class ViewTagRequest
{
    public function __construct(
        public string $id
    ) {}
}
