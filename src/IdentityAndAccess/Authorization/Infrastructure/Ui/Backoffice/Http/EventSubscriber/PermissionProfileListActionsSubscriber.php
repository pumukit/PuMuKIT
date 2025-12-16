<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\IdentityAndAccess\Authorization\Infrastructure\Ui\Backoffice\Http\Event\PermissionProfileListActionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PermissionProfileListActionsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PermissionProfileListActionsEvent::NAME => 'onListActions',
        ];
    }

    public function onListActions(PermissionProfileListActionsEvent $event): void
    {
        $event->addAction(
            key: 'permission_profile_create',
            label: 'Create',
            url: 'permission_profile_create',
            type: 'route',
            icon: 'fa-plus',
            class: 'btn btn-pumukit',
            priority: 10
        );
    }
}

