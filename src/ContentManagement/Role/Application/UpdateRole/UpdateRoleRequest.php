<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\UpdateRole;

final class UpdateRoleRequest
{
    public function __construct(
        public string $id,
        public array $name,
        public array $text = [],
        public ?string $xml = null,
        public bool $display = true,
    ) {}
}
