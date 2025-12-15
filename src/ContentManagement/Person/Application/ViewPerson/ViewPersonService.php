<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\ViewPerson;

use App\ContentManagement\Person\Domain\Exception\PersonNotFoundException;
use App\ContentManagement\Person\Domain\Repository\PersonRepositoryInterface;
use Pumukit\SchemaBundle\Document\Person;

final class ViewPersonService
{
    public function __construct(
        private readonly PersonRepositoryInterface $personRepository
    ) {}

    public function __invoke(ViewPersonRequest $request): ViewPersonResponse
    {
        ViewPersonValidator::validate($request);

        $person = $this->personRepository->find($request->id);
        if (!$person instanceof Person) {
            throw PersonNotFoundException::withId($request->id);
        }

        return new ViewPersonResponse($person);
    }
}
