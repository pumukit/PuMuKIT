<?php

declare(strict_types=1);

namespace App\MultimediaObject\UI\Backoffice\EventSubscriber;

use App\Shared\UI\Backoffice\Menu\Event\MenuBuildEvent;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MultimediaObjectMenuSubscriber implements EventSubscriberInterface
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
            key: 'multimedia_object_catalog',
            label: 'UNESCO Catalog',
            route: 'transcoding_index',
            parent: 'media_manager',
            icon: 'fa-photo-video',
            priority: 100,
            permission: Permission::ACCESS_MULTIMEDIA_SERIES
        );
    }
}

