<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

use Pumukit\CoreBundle\Event\InboxUploadEvent;
use Pumukit\CoreBundle\Event\UploadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UploadDispatcherService
{
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function dispatchUploadFromInbox(UserInterface $user, string $fileName): void
    {
        $event = new InboxUploadEvent($user, $fileName);
        $this->dispatcher->dispatch($event, UploadEvents::UPLOAD_FROM_INBOX);
    }
}
