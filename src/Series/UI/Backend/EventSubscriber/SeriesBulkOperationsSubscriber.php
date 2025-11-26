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
            key: 'announce',
            label: 'Announce | Not announce',
            handler: '#',
            type: 'route',
            icon: 'fa-download',
            confirmMessage: 'Do you want announce all series selected?',
            priority: 10
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
