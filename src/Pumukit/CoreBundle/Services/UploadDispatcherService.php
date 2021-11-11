<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Services;

use Pumukit\CoreBundle\Event\InboxUploadEvent;
use Pumukit\CoreBundle\Event\UploadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Filesystem\Filesystem;

class UploadDispatcherService
{
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function dispatchUploadFromInbox(UserInterface $user, string $fileName, string $folder): void
    {
        $event = new InboxUploadEvent($user, $fileName, $folder);
        $this->dispatcher->dispatch(UploadEvents::UPLOAD_FROM_INBOX, $event);
    }

    public function createFolderIfNotExists(string $folder)  
    {
        try {
            $filesystem = new Filesystem();
            if (!$filesystem->exists($folder)) {
                $filesystem->mkdir($folder);
            }
            
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}
