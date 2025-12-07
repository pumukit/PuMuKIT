<?php

declare(strict_types=1);

namespace App\UI\Backoffice\IdentityAndAccess\EventSubscriber;

use App\UI\Backoffice\IdentityAndAccess\Event\UserBulkOperationsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UserBulkOperationsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserBulkOperationsEvent::NAME => 'onBulkOperations',
        ];
    }

    public function onBulkOperations(UserBulkOperationsEvent $event): void
    {
        $event->addOperation(
            key: 'delete',
            label: 'Delete',
            handler: '#',
            type: 'route',
            icon: 'fa-trash',
            confirmMessage: 'Are you sure you want to delete the selected users? This action cannot be undone.',
            priority: 100
        );
    }
}
