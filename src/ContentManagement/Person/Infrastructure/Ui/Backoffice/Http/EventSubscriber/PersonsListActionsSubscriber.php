<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\ContentManagement\Person\Infrastructure\Ui\Backoffice\Http\Event\PersonListActionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PersonsListActionsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PersonListActionsEvent::NAME => 'onListActions',
        ];
    }

    public function onListActions(PersonListActionsEvent $event): void
    {
        $event->addAction(
            key: 'persons_create',
            label: 'Create',
            url: 'person_create',
            type: 'route',
            icon: 'fa-plus',
            class: 'btn btn-pumukit',
            priority: 10
        );
    }
}
