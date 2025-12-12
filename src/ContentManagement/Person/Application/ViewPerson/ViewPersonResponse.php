<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\ViewPerson;

use Pumukit\SchemaBundle\Document\Person;

final class ViewPersonResponse
{
    public function __construct(
        public Person $person
    ) {}
}
