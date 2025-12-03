<?php

declare(strict_types=1);

namespace App\Playlist\UI\Backoffice\EventSubscriber;

use App\Shared\UI\Backoffice\Menu\Event\MenuBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PlaylistMenuSubscriber implements EventSubscriberInterface
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
            key: 'playlist',
            label: 'Playlist',
            route: 'playlist_list',
            parent: 'media_manager',
            icon: 'fa-list',
            priority: 200,
        );
    }
}
