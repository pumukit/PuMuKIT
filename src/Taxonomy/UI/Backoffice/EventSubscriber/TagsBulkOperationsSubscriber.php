<?php

declare(strict_types=1);

namespace App\Taxonomy\UI\Backoffice\EventSubscriber;

use App\Taxonomy\UI\Backoffice\Event\TagBulkOperationsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TagsBulkOperationsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TagBulkOperationsEvent::NAME => 'onBulkOperations',
        ];
    }

    public function onBulkOperations(TagBulkOperationsEvent $event): void
    {
        $event->addOperation(
            key: 'delete',
            label: 'Delete',
            handler: 'taxonomy_tag_bulk_delete',
            type: 'route',
            icon: 'fa-trash',
            confirmMessage: 'Are you sure you want to delete the selected tags? This action cannot be undone and will remove all children.',
            priority: 100
        );
    }
}
