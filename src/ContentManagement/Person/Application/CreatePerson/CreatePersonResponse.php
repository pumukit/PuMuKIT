<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\CreatePerson;

use Pumukit\SchemaBundle\Document\Person;

final readonly class CreatePersonResponse
{
    public function __construct(
        public Person $person
    ) {}
}
