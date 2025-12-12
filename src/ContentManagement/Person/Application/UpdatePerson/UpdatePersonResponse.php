<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\UpdatePerson;

use Pumukit\SchemaBundle\Document\Person;

final readonly class UpdatePersonResponse
{
    public function __construct(
        public Person $person
    ) {}
}
