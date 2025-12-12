<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\ListPersons;

use App\ContentManagement\Person\Domain\Repository\PersonRepositoryInterface;

final readonly class ListPersonsService
{
    public function __construct(
        private PersonRepositoryInterface $personRepository
    ) {}

    public function __invoke(ListPersonsRequest $request): ListPersonsResponse
    {
        $persons = $this->personRepository->findAll();

        return new ListPersonsResponse($persons);
    }
}
