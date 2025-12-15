<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Streaming\Channel\EventSubscriber;

use App\Shared\Infrastructure\Ui\Backoffice\Menu\Event\MenuBuildEvent;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class StreamingMenuSubscriber implements EventSubscriberInterface
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
            key: 'streaming_channels',
            label: 'Channels',
            route: 'streaming_channels_list',
            parent: 'streaming',
            icon: 'fa-broadcast-tower',
            priority: 100,
            permission: Permission::ACCESS_LIVE_CHANNELS
        );
    }
}
