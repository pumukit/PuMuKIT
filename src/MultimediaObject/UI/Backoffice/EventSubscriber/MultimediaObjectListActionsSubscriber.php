<?php

declare(strict_types=1);

namespace App\MultimediaObject\UI\Backoffice\EventSubscriber;

use App\MultimediaObject\UI\Backoffice\Event\MultimediaObjectListActionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MultimediaObjectListActionsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MultimediaObjectListActionsEvent::NAME => 'onListActions',
        ];
    }

    public function onListActions(MultimediaObjectListActionsEvent $event): void
    {
        $event->addAction(
            key: 'multimedia_object_create',
            label: 'Create',
            url: '#',
            type: 'route',
            icon: 'fa-plus',
            class: 'btn btn-pumukit',
            priority: 10
        );
    }
}
