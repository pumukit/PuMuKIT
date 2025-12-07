<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Streaming\EventSubscriber;

use App\UI\Backoffice\Streaming\Event\ChannelListActionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ChannelListActionsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ChannelListActionsEvent::NAME => 'onListActions',
        ];
    }

    public function onListActions(ChannelListActionsEvent $event): void
    {
        $event->addAction(
            key: 'channel_create',
            label: 'Crear',
            url: 'streaming_channel_create',
            type: 'route',
            icon: 'fa-plus',
            class: 'btn btn-pumukit',
            priority: 10
        );
    }
}
