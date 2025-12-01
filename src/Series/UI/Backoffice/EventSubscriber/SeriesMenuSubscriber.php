<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\EventSubscriber;

use App\Shared\UI\Backoffice\Menu\Event\MenuBuildEvent;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SeriesMenuSubscriber implements EventSubscriberInterface
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
            key: 'series',
            label: 'Series',
            route: 'series_list',
            parent: 'media_manager',
            icon: 'fa-list',
            priority: 100,
            permission: Permission::ACCESS_MULTIMEDIA_SERIES
        );
    }
}

