<?php

declare(strict_types=1);

namespace App\Series\UI\Backend\EventSubscriber;

use App\Series\UI\Backend\Event\SeriesBulkOperationsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SeriesBulkOperationsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SeriesBulkOperationsEvent::NAME => 'onBulkOperations',
        ];
    }

    public function onBulkOperations(SeriesBulkOperationsEvent $event): void
    {
        $event->addOperation(
            key: 'toggle_announce',
            label: 'Toggle Announce',
            handler: 'series_bulk_toggle_announce',
            type: 'route',
            icon: 'fa-bullhorn',
            confirmMessage: 'Toggle announce status for selected series?',
            priority: 90
        );

        $event->addOperation(
            key: 'delete',
            label: 'Delete',
            handler: 'series_bulk_delete',
            type: 'route',
            icon: 'fa-trash',
            confirmMessage: 'Are you sure you want to delete the selected series? This action cannot be undone.',
            priority: 100
        );
    }
}
