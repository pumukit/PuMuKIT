<?php

namespace Pumukit\SchemaBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Pumukit\SchemaBundle\Document\User;

class UserEvent extends Event
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
