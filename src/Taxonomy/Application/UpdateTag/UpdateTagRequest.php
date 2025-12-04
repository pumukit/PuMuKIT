<?php

declare(strict_types=1);

namespace App\Taxonomy\Application\UpdateTag;

final readonly class UpdateTagRequest
{
    public function __construct(
        public string $id,
        public array $title,
        public array $description,
        public ?string $slug,
        public bool $metatag,
        public bool $display,
        public array $properties
    ) {}
}
