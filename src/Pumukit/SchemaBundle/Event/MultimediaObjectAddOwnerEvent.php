<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Event;

use FOS\UserBundle\Model\UserInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\EventDispatcher\Event;

class MultimediaObjectAddOwnerEvent extends Event
{
    protected $multimediaObject;
    protected $user;

    public function __construct(MultimediaObject $multimediaObject, UserInterface $user)
    {
        $this->multimediaObject = $multimediaObject;
        $this->user = $user;
    }

    public function getMultimediaObject(): MultimediaObject
    {
        return $this->multimediaObject;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }
}
