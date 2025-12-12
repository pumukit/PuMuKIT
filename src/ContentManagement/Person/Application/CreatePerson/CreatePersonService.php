<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Application\CreatePerson;

use App\ContentManagement\Person\Domain\Event\PersonCreatedEvent;
use App\ContentManagement\Person\Domain\Repository\PersonRepositoryInterface;
use App\Shared\Domain\EventBusInterface;
use Pumukit\SchemaBundle\Document\Person;

final readonly class CreatePersonService
{
    public function __construct(
        private PersonRepositoryInterface $personRepository,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(CreatePersonRequest $request): CreatePersonResponse
    {
        $person = new Person();
        $person->setName($request->name);
        $person->setEmail($request->email);
        $person->setWeb($request->web);
        $person->setPhone($request->phone);

        if (!empty($request->honorific)) {
            $person->setI18nHonorific($request->honorific);
        }

        if (!empty($request->firm)) {
            $person->setI18nFirm($request->firm);
        }

        if (!empty($request->post)) {
            $person->setI18nPost($request->post);
        }

        if (!empty($request->bio)) {
            $person->setI18nBio($request->bio);
        }

        $this->personRepository->save($person);

        $event = PersonCreatedEvent::fromPerson(
            $person->getId(),
            $person->getName(),
            $person->getEmail()
        );
        $this->eventBus->dispatch($event);

        return new CreatePersonResponse($person);
    }
}
