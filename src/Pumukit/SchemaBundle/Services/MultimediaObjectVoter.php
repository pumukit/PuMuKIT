<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MultimediaObjectVoter extends Voter
{
    public const EDIT = 'edit';
    public const PLAY = 'play';
    public const VIEW_METADATA = 'view_metadata';

    private $mmobjService;
    private $requestStack;

    public function __construct(MultimediaObjectService $mmobjService, RequestStack $requestStack)
    {
        $this->mmobjService = $mmobjService;
        $this->requestStack = $requestStack;
    }

    protected function supports($attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::EDIT, self::PLAY, self::VIEW_METADATA])) {
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

            case self::VIEW_METADATA:
                return $this->canViewMetadata($multimediaObject, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    protected function canEdit(MultimediaObject $multimediaObject, $user = null)
    {
        if ($user instanceof User && ($user->hasRole(PermissionProfile::SCOPE_GLOBAL) || $user->hasRole('ROLE_SUPER_ADMIN'))) {
            return true;
        }

        if ($user instanceof User && $user->hasRole(PermissionProfile::SCOPE_PERSONAL) && $this->mmobjService->isUserOwner($user, $multimediaObject)) {
            return true;
        }

        return false;
    }

    protected function canPlay(MultimediaObject $multimediaObject, $user = null)
    {
        // Private play
        if ($this->canEdit($multimediaObject, $user)) {
            return true;
        }

        // Test broadcast
        $embeddedBroadcast = $multimediaObject->getEmbeddedBroadcastNotNull();
        if (EmbeddedBroadcast::TYPE_LOGIN === $embeddedBroadcast->getType()) {
            if (!$user instanceof User || !$this->isViewerOrWithScope($user)) {
                return false;
            }
        }
        if (EmbeddedBroadcast::TYPE_GROUPS === $embeddedBroadcast->getType()) {
            if (!$user instanceof User || !$this->isViewerOrWithScope($user) || !$this->isUserRelatedToBroadcast($multimediaObject->getEmbeddedBroadcastNotNull(), $user)) {
                return false;
            }
        }

        /*
         * NOTE: (OUT OF SCOPE) Share URL and password is as easy as share only the URL.
         *      if (EmbeddedBroadcast::TYPE_PASSWORD === $embeddedBroadcast->getType()) {
         *          $password = $this->requestStack->getMasterRequest()->get('broadcast_password');
         *          if ($password != $embeddedBroadcast->getPassword()) {
         *              return false;
         *          }
         *      }
        */

        // Public play
        if ($this->mmobjService->isHidden($multimediaObject, PumukitWebTVBundle::WEB_TV_TAG) || $this->mmobjService->isHidden($multimediaObject, 'PUCHPODCAST')) {
            return true;
        }

        // Legacy code
        if ($this->mmobjService->isHidden($multimediaObject, 'PUCHOPENEDX')) {
            return true;
        }

        // TTK-24312 WA to show public videos with Poddium channel
        if ($this->mmobjService->isPublished($multimediaObject, 'PUCHPODDIUM')) {
            return true;
        }

        return false;
    }

    protected function canViewMetadata(MultimediaObject $multimediaObject, $user = null)
    {
        // Private play
        if ($this->canEdit($multimediaObject, $user)) {
            return true;
        }

        // Public play
        if ($this->mmobjService->isHidden($multimediaObject, PumukitWebTVBundle::WEB_TV_TAG) || $this->mmobjService->isHidden($multimediaObject, 'PUCHPODCAST')) {
            return true;
        }

        // Legacy code
        if ($this->mmobjService->isHidden($multimediaObject, 'PUCHOPENEDX')) {
            return true;
        }

        return false;
    }

    protected function isViewerOrWithScope(User $user)
    {
        return $user->hasRole(PermissionProfile::SCOPE_GLOBAL) || $user->hasRole(PermissionProfile::SCOPE_PERSONAL)
                || $user->hasRole(PermissionProfile::SCOPE_NONE) || $user->hasRole('ROLE_SUPER_ADMIN');
    }

    // Related to EmbeddedBroadcastService::isUserRelatedToMultimediaObject
    protected function isUserRelatedToBroadcast(EmbeddedBroadcast $broadcast, User $user)
    {
        $userGroups = $user->getGroups()->toArray();
        $playGroups = $broadcast->getGroups()->toArray();

        return array_intersect($playGroups, $userGroups);
    }
}
