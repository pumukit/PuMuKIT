<?php

declare(strict_types=1);

namespace App\Streaming\UI\Backoffice\EventSubscriber;

use App\Shared\UI\Backoffice\Menu\Event\MenuBuildEvent;
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
            route: 'streaming_index',
            parent: 'streaming',
            icon: 'fa-list',
            priority: 200,
        );
    }
}

