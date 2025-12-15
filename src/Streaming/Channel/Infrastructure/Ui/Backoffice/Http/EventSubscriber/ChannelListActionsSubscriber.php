<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\Streaming\Channel\Infrastructure\Ui\Backoffice\Http\Event\ChannelListActionsEvent;
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
            label: 'Create',
            url: 'backoffice_streaming_channel_create',
            type: 'route',
            icon: 'fa-plus',
            class: 'btn btn-pumukit',
            priority: 10
        );
    }
}
