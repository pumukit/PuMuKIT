<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Event\InboxUploadEvent;
use Pumukit\CoreBundle\Event\UploadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UploadDispatcherService
{
    private $dispatcher;
    private LoggerInterface $logger;

    public function __construct(EventDispatcherInterface $dispatcher, LoggerInterface $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    public function dispatchUploadFromInbox(UserInterface $user, string $fileName, string $seriesId): void
    {
        $event = new InboxUploadEvent($user, $fileName, $seriesId);
        $this->dispatcher->dispatch($event, UploadEvents::UPLOAD_FROM_INBOX);
    }
}
