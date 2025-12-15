<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\ContentManagement\MultimediaObject\Infrastructure\Ui\Backoffice\Http\Event\MultimediaObjectListActionsEvent;
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
