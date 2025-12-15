<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\MultimediaObject\EventSubscriber;

use App\Shared\Infrastructure\Ui\Backoffice\Http\Event\MenuBuildEvent;
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
            label: 'Media',
            route: 'multimedia_objects_list',
            parent: 'media_manager',
            icon: 'fa-photo-video',
            priority: 100,
            permission: Permission::ACCESS_MULTIMEDIA_SERIES
        );
    }
}
