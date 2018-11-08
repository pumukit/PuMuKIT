<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\HttpFoundation\RequestStack;

class MultimediaObjectVoter extends Voter
{
    const EDIT = 'edit';
    const PLAY = 'play';

    private $mmobjService;
    private $embeddedBroadcastService;
    private $requestStack;

    public function __construct(MultimediaObjectService $mmobjService, EmbeddedBroadcastService $embeddedBroadcastService, RequestStack $requestStack)
    {
        $this->mmobjService = $mmobjService;
        $this->embeddedBroadcastService = $embeddedBroadcastService;
        $this->requestStack = $requestStack;
    }

    protected function supports($attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, array(self::EDIT, self::PLAY))) {
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
        case self::EDIT:
            return $this->canEdit($multimediaObject, $user);
        case self::PLAY:
            return $this->canPlay($multimediaObject, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    protected function canEdit($multimediaObject, $user = null)
    {
        if ($user instanceof User && ($user->hasRole(PermissionProfile::SCOPE_GLOBAL) || $user->hasRole('ROLE_SUPER_ADMIN'))) {
            return true;
        }

        if ($user instanceof User && $user->hasRole(PermissionProfile::SCOPE_PERSONAL) && $this->mmobjService->isUserOwner($user, $multimediaObject)) {
            return true;
        }

        return false;
    }

    protected function canPlay($multimediaObject, $user = null)
    {
        // Private play
        if ($this->canEdit($multimediaObject, $user)) {
            return true;
        }

        // Test broadcast
        $password = $this->requestStack->getMasterRequest()->get('broadcast_password');
        if (!$this->embeddedBroadcastService->canUserPlayMultimediaObject($multimediaObject, $user, $password)) {
            return false;
        }

        // Public play
        if ($this->mmobjService->isHidden($multimediaObject, 'PUCHWEBTV') || $this->mmobjService->isHidden($multimediaObject, 'PUCHPODCAST')) {
            return true;
        }

        /* Legacy code */
        if ($this->mmobjService->isHidden($multimediaObject, 'PUCHOPENEDX')) {
            return true;
        }

        return false;
    }
}
