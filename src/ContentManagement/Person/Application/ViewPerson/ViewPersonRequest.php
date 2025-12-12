<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\ViewPerson;

final class ViewPersonRequest
{
    public function __construct(
        public string $id
    ) {}
}
