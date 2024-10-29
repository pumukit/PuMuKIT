<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

class UploadFileEvent extends Event
{
    protected UserInterface $user;
    protected string $fileName;
    protected string $series;
    protected string $profile;

    public function __construct(UserInterface $user, string $fileName, string $series, string $profile)
    {
        $this->user = $user;
        $this->fileName = $fileName;
        $this->series = $series;
        $this->profile = $profile;
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

    public function getProfile(): string
    {
        return $this->profile;
    }
}
