<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\DeletePerson;

use App\ContentManagement\Person\Domain\Event\PersonDeletedEvent;
use App\ContentManagement\Person\Domain\Exception\PersonNotFoundException;
use App\ContentManagement\Person\Domain\Repository\PersonRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final readonly class DeletePersonService
{
    public function __construct(
        private PersonRepositoryInterface $personRepository,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(DeletePersonRequest $request): void
    {
        $person = $this->personRepository->find($request->id);
        if (!$person) {
            throw PersonNotFoundException::withId($request->id);
        }

        $personId = $person->getId();
        $personName = $person->getName();

        $this->personRepository->delete($person);

        $event = PersonDeletedEvent::fromPerson($personId, $personName);
        $this->eventBus->dispatch($event);
    }
}
