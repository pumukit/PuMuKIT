<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\User;

trait LoggedInUserTrait
{
    protected function loggedInUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
