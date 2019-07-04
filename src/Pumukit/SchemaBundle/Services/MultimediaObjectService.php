<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;

class MultimediaObjectService
{
    private $dm;
    private $repo;
    private $dispatcher;
    private $seriesRepo;

    public function __construct(DocumentManager $documentManager, MultimediaObjectEventDispatcherService $dispatcher)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository(MultimediaObject::class);
        $this->seriesRepo = $this->dm->getRepository(Series::class);
        $this->dispatcher = $dispatcher;
    }

    /**
     * Returns true if the $mm is published. ( Keep updated with SchemaFilter->getCriteria() ).
     *
     * @param MultimediaObject
     * @param mixed $mm
     * @param mixed $pubChannelCod
     *
     * @return bool
     */
    public function isPublished($mm, $pubChannelCod)
    {
        $hasStatus = MultimediaObject::STATUS_PUBLISHED == $mm->getStatus();
        $hasPubChannel = $mm->containsTagWithCod($pubChannelCod);

        return $hasStatus && $hasPubChannel;
    }

    /**
     * Returns true if the $mm is hidden. Not 404 on its magic url. ( Keep updated with MultimediaObjectController:magicIndexAction ).
     *
     * @param MultimediaObject
     * @param Publication channel code
     * @param mixed $mm
     * @param mixed $pubChannelCod
     *
     * @return bool
     */
    public function isHidden($mm, $pubChannelCod)
    {
        $hasStatus = in_array($mm->getStatus(), [MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDDEN]);
        $hasPubChannel = $mm->containsTagWithCod($pubChannelCod);

        return $hasStatus && $hasPubChannel;
    }

    /**
     * Returns true if the $mm has a playable resource. ( Keep updated with SchemaFilter->getCriteria() ).
     *
     * @param MultimediaObject
     * @param mixed $mm
     *
     * @return bool
     */
    public function hasPlayableResource($mm)
    {
        $externalplayer = $mm->getProperty('externalplayer');

        return $mm->getDisplayTrack() || $mm->getProperty('opencast') || !empty($externalplayer);
    }

    /**
     * Returns true if the $mm is being displayed on the baseplayer. ( Keep updated with SchemaFilter->getCriteria() ).
     *
     * @param MultimediaObject
     * @param string
     * @param mixed $mm
     * @param mixed $pubChannelCod
     *
     * @return bool
     */
    public function canBeDisplayed($mm, $pubChannelCod)
    {
        return $this->isPublished($mm, $pubChannelCod) && $this->hasPlayableResource($mm);
    }

    /**
     * Resets the magic url for a given multimedia object. Returns the secret id.
     *
     * @param MultimediaObject
     * @param mixed $mm
     *
     * @return string
     */
    public function resetMagicUrl($mm)
    {
        $mm->resetSecret();
        $this->dm->persist($mm);
        $this->dm->flush();

        return $mm->getSecret();
    }

    /**
     * Update multimedia object.
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function updateMultimediaObject(MultimediaObject $multimediaObject)
    {
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->dispatcher->dispatchUpdate($multimediaObject);

        return $multimediaObject;
    }

    /**
     * Inc num view of multimedia object.
     *
     * @param MultimediaObject $multimediaObject
     */
    public function incNumView(MultimediaObject $multimediaObject)
    {
        $multimediaObject->incNumview();
        $this->updateMultimediaObject($multimediaObject);
    }

    /**
     * Add  group to multimediaObject.
     *
     * @param Group            $group
     * @param MultimediaObject $multimediaObject
     * @param bool             $executeFlush
     */
    public function addGroup(Group $group, MultimediaObject $multimediaObject, $executeFlush = true)
    {
        if (!$multimediaObject->containsGroup($group)) {
            $multimediaObject->addGroup($group);
            $this->dm->persist($multimediaObject);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($multimediaObject);
        }
    }

    /**
     * Delete  group to multimediaObject.
     *
     * @param Group            $group
     * @param MultimediaObject $multimediaObject
     * @param bool             $executeFlush
     */
    public function deleteGroup(Group $group, MultimediaObject $multimediaObject, $executeFlush = true)
    {
        if ($multimediaObject->containsGroup($group)) {
            $multimediaObject->removeGroup($group);
            $this->dm->persist($multimediaObject);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($multimediaObject);
        }
    }

    /**
     * Is user owner.
     *
     * @param User             $user
     * @param MultimediaObject $multimediaObject
     *
     * @return bool
     */
    public function isUserOwner(User $user, MultimediaObject $multimediaObject)
    {
        $userGroups = $user->getGroups()->toArray();
        $adminGroups = $multimediaObject->getGroups()->toArray();
        $commonAdminGroups = array_intersect($adminGroups, $userGroups);

        $userIsOwner = false;
        if ($owners = $multimediaObject->getProperty('owners')) {
            $userIsOwner = in_array($user->getId(), $owners);
        }

        return $commonAdminGroups || $userIsOwner;
    }

    /**
     * Delete all multimedia objects from group.
     *
     * @param Group
     */
    public function deleteAllFromGroup(Group $group)
    {
        $multimediaObjects = $this->repo->findWithGroup($group);
        foreach ($multimediaObjects as $multimediaObject) {
            $this->deleteGroup($group, $multimediaObject, false);
        }
        $this->dm->flush();
    }

    /**
     * Removes this multimedia object reference from all existing playlists.
     *
     * @param MultimediaObject $multimediaObject
     */
    public function removeFromAllPlaylists(MultimediaObject $multimediaObject)
    {
        $qb = $this->seriesRepo->createQueryBuilder()->field('playlist.multimedia_objects')->equals(new \MongoId($multimediaObject->getId()));
        //Doing some variation of this should work as well. (Not this code particularly).
        //$qb = $this->seriesRepo->createQueryBuilder()->field('playlist.multimedia_objects')->pullAll(array(new \MongoId($multimediaObject->getId())));
        $playlists = $qb->getQuery()->execute();
        foreach ($playlists as $playlist) {
            $playlist->getPlaylist()->removeAllMultimediaObjectsById($multimediaObject->getId());
            $this->dm->persist($playlist);
        }
        $this->dm->flush();
    }

    /**
     * Returns a boolean with whether the mmobj will be played on a playlist or not.
     *
     * @param MultimediaObject $mmobj
     *
     * @return bool
     */
    public function isPlayableOnPlaylist($mmobj)
    {
        $broadcast = $mmobj->getEmbeddedBroadcast();
        if (($broadcast && EmbeddedBroadcast::TYPE_PUBLIC != $broadcast->getType())
            || MultimediaObject::STATUS_PUBLISHED != $mmobj->getStatus()
            || !$this->hasPlayableResource($mmobj)) {
            return false;
        }

        return true;
    }
}
