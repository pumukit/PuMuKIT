<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\DeletePerson;

final class DeletePersonRequest
{
    public function __construct(
        public string $id
    ) {}
}
