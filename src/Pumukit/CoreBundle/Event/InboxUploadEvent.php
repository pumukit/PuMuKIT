<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

class InboxUploadEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    protected $user;

    protected $fileName;

    protected $folder;

    public function __construct(UserInterface $user, string $fileName, string $folder = null)
    {
        $this->user = $user;
        $this->fileName = $fileName;
        $this->folder = $folder;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFolder(): ?string
    {
        return $this->folder;
    }
}
