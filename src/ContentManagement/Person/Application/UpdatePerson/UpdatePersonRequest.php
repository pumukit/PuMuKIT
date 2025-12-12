<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\UpdatePerson;

final class UpdatePersonRequest
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email = null,
        public ?string $web = null,
        public ?string $phone = null,
        public array $honorific = [],
        public array $firm = [],
        public array $post = [],
        public array $bio = []
    ) {}
}
