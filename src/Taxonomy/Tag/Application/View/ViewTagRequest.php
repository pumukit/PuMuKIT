<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\View;

final class ViewTagRequest
{
    public function __construct(
        public string $id
    ) {}
}
