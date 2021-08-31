<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

class InboxUploadEvent extends Event
{
    protected $user;

    protected $fileName;

    public function __construct(UserInterface $user, string $fileName)
    {
        $this->user = $user;
        $this->fileName = $fileName;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }
}
