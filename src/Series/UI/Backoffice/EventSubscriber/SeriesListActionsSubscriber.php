<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\EventSubscriber;

use App\Series\UI\Backoffice\Event\SeriesListActionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SeriesListActionsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SeriesListActionsEvent::NAME => 'onListActions',
        ];
    }

    public function onListActions(SeriesListActionsEvent $event): void
    {
        $event->addAction(
            key: 'series_create',
            label: 'Create',
            url: 'series_create',
            type: 'route',
            icon: 'fa-plus',
            class: 'btn btn-pumukit',
            priority: 10
        );
    }
}
