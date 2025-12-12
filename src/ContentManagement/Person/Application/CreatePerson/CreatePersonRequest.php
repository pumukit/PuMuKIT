<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\CreatePerson;

final readonly class CreatePersonRequest
{
    public function __construct(
        public string $name,
        public array $bio = [],
        public array $post = [],
        public array $firm = [],
        public array $honorific = [],
        public ?string $phone = null,
        public ?string $web = null,
        public ?string $email = null,
    ) {}
}
