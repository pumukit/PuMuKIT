<?php

declare(strict_types=1);

namespace App\User\UI\Backoffice\EventSubscriber;

use App\User\UI\Backoffice\Event\UserListActionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UserListActionsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserListActionsEvent::NAME => 'onListActions',
        ];
    }

    public function onListActions(UserListActionsEvent $event): void
    {
        $event->addAction(
            key: 'user_create',
            label: 'Create',
            url: 'user_create',
            type: 'route',
            icon: 'fa-plus',
            class: 'btn btn-pumukit',
            priority: 10
        );
    }
}
