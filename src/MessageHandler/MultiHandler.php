<?php
namespace App\MessageHandler;

use App\Message\WaitingMessage;
use App\Message\EventMessage;
use App\Message\NotificationMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

class MultiHandler
{
    #[AsMessageHandler]
    public function handleWaiting(WaitingMessage $message)
    {
        dump("📥 Waiting queue: " . $message->getContent());
    }

    #[AsMessageHandler]
    public function handleEvents(EventMessage $message)
    {
        dump("🎉 Event queue: " . $message->getContent());
    }

    #[AsMessageHandler]
    public function handleNotifications(NotificationMessage $message)
    {
        dump("🔔 Notification queue: " . $message->getContent());
    }
}
