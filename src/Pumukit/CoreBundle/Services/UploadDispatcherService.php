<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Event\UploadEvents;
use Pumukit\CoreBundle\Event\UploadFileEvent;
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

    public function dispatchUploadFromInbox(UserInterface $user, string $fileName, string $seriesId, string $profile): void
    {
        $event = new UploadFileEvent($user, $fileName, $seriesId, $profile);
        $this->dispatcher->dispatch($event, UploadEvents::UPLOAD_FROM_INBOX);
    }

    public function dispatchUploadFromServer(UserInterface $user, string $fileName, string $seriesId, string $profile): void
    {
        $event = new UploadFileEvent($user, $fileName, $seriesId, $profile);
        $this->dispatcher->dispatch($event, UploadEvents::UPLOAD_FROM_SERVER);
    }
}
