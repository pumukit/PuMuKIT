<?php

declare(strict_types=1);

namespace App\UI\Backoffice\IdentityAndAccess\EventSubscriber;

use App\UI\Backoffice\Shared\Menu\Event\MenuBuildEvent;
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
            permission: Permission::ACCESS_ADMIN_USERS
        );
    }
}
