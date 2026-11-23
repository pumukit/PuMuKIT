<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Contracts\EventDispatcher\Event;

class MultimediaObjectAddOwnerEvent extends Event
{
    protected $multimediaObject;
    protected $user;
    protected $coOwner;

    public function __construct(MultimediaObject $multimediaObject, User $user, User $coOwner)
    {
        $this->multimediaObject = $multimediaObject;
        $this->user = $user;
        $this->coOwner = $coOwner;
    }

    public function getMultimediaObject(): MultimediaObject
    {
        return $this->multimediaObject;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCoOwner(): User
    {
        return $this->coOwner;
    }
}
