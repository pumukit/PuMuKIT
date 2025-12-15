<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\Shared\Infrastructure\Ui\Backoffice\Http\Event\MenuBuildEvent;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UserMenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MenuBuildEvent::NAME => 'onMenuBuild',
        ];
    }

    public function onMenuBuild(MenuBuildEvent $event): void
    {
        $event->addItem(
            key: 'user',
            label: 'Users',
            route: 'user_list',
            parent: 'management',
            icon: 'fa-user',
            priority: 100,
            permission: Permission::ACCESS_ADMIN_USERS,
            activeRoutes: ['user_create', 'user_view', 'user_update', 'user_delete', 'users_list_data']
        );
    }
}
