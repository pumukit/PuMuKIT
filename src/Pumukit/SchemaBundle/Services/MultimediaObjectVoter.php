<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MultimediaObjectVoter extends Voter
{
    const PLAY = 'play';

    private $mmobjService;

    public function __construct(MultimediaObjectService $mmobjService)
    {
        $this->mmobjService = $mmobjService;
    }

    protected function supports($attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, array(self::PLAY))) {
            return false;
        }

        // only vote on Post objects inside this voter
        if (!$subject instanceof MultimediaObject) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $multimediaObject, TokenInterface $token)
    {
        $user = $token->getUser();

        switch ($attribute) {
        case self::PLAY:
            return $this->canPlay($multimediaObject, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    protected function canPlay($multimediaObject, $user = null)
    {
        //Private playo
        if ($user && ($user->hasRole(PermissionProfile::SCOPE_GLOBAL) || $user->hasRole('ROLE_SUPER_ADMIN'))) {
            return true;
        }

        if ($user && $user->hasRole(PermissionProfile::SCOPE_PERSONAL) && $this->mmobjService->isUserOwner($user, $multimediaObject)) {
            return true;
        }

        // Public play
        if ($this->mmobjService->isHidden($multimediaObject, 'PUCHWEBTV') || $this->mmobjService->isHidden($multimediaObject, 'PUCHPODCAST')) {
            return true;
        }

        /* Legacy code */
        if ($this->mmobjService->isHidden($multimediaObject, 'PUCHMOODLE') || $this->mmobjService->isHidden($multimediaObject, 'PUCHOPENEDX')) {
            return true;
        }

        return false;
    }
}
