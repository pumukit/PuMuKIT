<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\UpdatePerson;

use App\ContentManagement\Person\Domain\Event\PersonUpdatedEvent;
use App\ContentManagement\Person\Domain\Exception\PersonNotFoundException;
use App\ContentManagement\Person\Domain\Repository\PersonRepositoryInterface;
use App\Shared\Domain\EventBusInterface;
use Pumukit\SchemaBundle\Document\Person;

final class UpdatePersonService
{
    public function __construct(
        private readonly PersonRepositoryInterface $personRepository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(UpdatePersonRequest $request): UpdatePersonResponse
    {
        UpdatePersonValidator::validate($request);

        $person = $this->personRepository->find($request->id);
        if (!$person instanceof Person) {
            throw PersonNotFoundException::withId($request->id);
        }

        $person->setName($request->name);
        $person->setEmail($request->email);
        $person->setWeb($request->web);
        $person->setPhone($request->phone);
        $person->setI18nHonorific($request->honorific);
        $person->setI18nFirm($request->firm);
        $person->setI18nPost($request->post);
        $person->setI18nBio($request->bio);

        $this->personRepository->save($person);

        $event = PersonUpdatedEvent::fromPerson(
            $person->getId(),
            $person->getName(),
            $person->getEmail()
        );
        $this->eventBus->dispatch($event);

        return new UpdatePersonResponse($person);
    }
}
