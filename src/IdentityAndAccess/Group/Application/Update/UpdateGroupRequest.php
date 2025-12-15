<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\Update;

final class UpdateGroupRequest
{
    public function __construct(
        public string $id,
        public ?string $key = null,
        public ?string $name = null,
        public ?string $comments = null,
        public ?string $origin = null
    ) {}
}
