<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\Shared\Infrastructure\Ui\Backoffice\Http\Event\MenuBuildEvent;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PermissionProfileMenuSubscriber implements EventSubscriberInterface
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
            key: 'permission_profile',
            label: 'Permission Profiles',
            route: 'permission_profile_list',
            parent: 'management',
            icon: 'fa-shield-alt',
            priority: 85,
            permission: Permission::ACCESS_ADMIN_USERS,
            activeRoutes: ['permission_profile_create', 'permission_profile_view', 'permission_profile_update', 'permission_profile_delete', 'permission_profiles_list_data']
        );
    }
}
