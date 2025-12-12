<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\ListPersons;

final class ListPersonsResponse
{
    public function __construct(
        public array $persons
    ) {}
}
