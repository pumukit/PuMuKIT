<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\ListPersons;

use App\ContentManagement\Person\Domain\Repository\PersonRepositoryInterface;

final class ListPersonsService
{
    public function __construct(
        private readonly PersonRepositoryInterface $personRepository
    ) {}

    public function __invoke(ListPersonsRequest $request): ListPersonsResponse
    {
        ListPersonsValidator::validate($request);

        $persons = $this->personRepository->findAll();

        return new ListPersonsResponse($persons);
    }
}
