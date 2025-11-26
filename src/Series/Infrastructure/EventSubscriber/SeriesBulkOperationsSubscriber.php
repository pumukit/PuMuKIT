<?php

declare(strict_types=1);

namespace App\Series\Infrastructure\EventSubscriber;

use App\Series\Domain\Event\SeriesBulkOperationsEvent;
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
            handler: '#',
            type: 'route',
            icon: 'fa-trash',
            confirmMessage: 'Do you want delete all series selected?',
            priority: 100
        );
    }
}

