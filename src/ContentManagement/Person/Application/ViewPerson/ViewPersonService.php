<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\ViewPerson;

use Pumukit\SchemaBundle\Document\Person;
use App\ContentManagement\Person\Domain\Exception\PersonNotFoundException;
use App\ContentManagement\Person\Domain\Repository\PersonRepositoryInterface;

final class ViewPersonService
{
    public function __construct(
        private readonly PersonRepositoryInterface $personRepository
    ) {}

    public function __invoke(ViewPersonRequest $request): ViewPersonResponse
    {
        $person = $this->personRepository->find($request->id);
        if (!$person instanceof Person) {
            throw PersonNotFoundException::withId($request->id);
        }

        return new ViewPersonResponse($person);
    }
}
