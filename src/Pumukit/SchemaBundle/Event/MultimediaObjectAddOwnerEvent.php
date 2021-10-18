<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

class MultimediaObjectAddOwnerEvent extends Event
{
    protected $multimediaObject;
    protected $user;
    protected $coOwner;

    public function __construct(MultimediaObject $multimediaObject, UserInterface $user, UserInterface $coOwner)
    {
        $this->multimediaObject = $multimediaObject;
        $this->user = $user;
        $this->coOwner = $coOwner;
    }

    public function getMultimediaObject(): MultimediaObject
    {
        return $this->multimediaObject;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getCoOwner(): UserInterface
    {
        return $this->coOwner;
    }
}
