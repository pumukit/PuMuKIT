<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Application\CreateRole;

final class CreateRoleRequest
{
    public function __construct(
        public string $cod,
        public array $name,
        public array $text = [],
        public ?string $xml = null,
        public bool $readOnly = false,
        public bool $display = true,
    ) {}
}
