<?php

declare(strict_types=1);

namespace App\Taxonomy\Application\ViewTag;

final readonly class ViewTagRequest
{
    public function __construct(
        public string $id
    ) {}
}

