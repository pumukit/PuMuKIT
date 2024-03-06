<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

class InboxUploadEvent extends Event
{
    protected $user;

    protected $fileName;

    protected $series;

    public function __construct(UserInterface $user, string $fileName, string $series)
    {
        $this->user = $user;
        $this->fileName = $fileName;
        $this->series = $series;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getSeries(): string
    {
        return $this->series;
    }
}
