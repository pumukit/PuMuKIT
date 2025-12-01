<?php

declare(strict_types=1);

namespace App\Taxonomy\Application\CreateTag;

final readonly class CreateTagRequest
{
    public function __construct(
        public string $cod,
        public array $title,
        public array $description = [],
        public ?string $slug = null,
        public bool $metatag = false,
        public bool $display = false,
        public ?string $parentId = null,
        public array $properties = []
    ) {}
}

