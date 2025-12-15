<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Application\Create;

final class CreateGroupRequest
{
    public function __construct(
        public string $key,
        public string $name,
        public ?string $comments = null,
        public string $origin = 'local'
    ) {}
}
