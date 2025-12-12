<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\ViewPerson;

use App\ContentManagement\Person\Domain\Exception\PersonNotFoundException;
use App\ContentManagement\Person\Domain\Repository\PersonRepositoryInterface;

final readonly class ViewPersonService
{
    public function __construct(
        private PersonRepositoryInterface $personRepository
    ) {}

    public function __invoke(ViewPersonRequest $request): ViewPersonResponse
    {
        $person = $this->personRepository->find($request->id);
        if (!$person) {
            throw PersonNotFoundException::withId($request->id);
        }

        return new ViewPersonResponse($person);
    }
}
