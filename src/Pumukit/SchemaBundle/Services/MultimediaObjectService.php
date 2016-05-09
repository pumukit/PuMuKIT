<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;


class MultimediaObjectService
{
    private $dm;
    private $repo;
    private $dispatcher;

    public function __construct(DocumentManager $documentManager, MultimediaObjectEventDispatcherService $dispatcher)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->dispatcher = $dispatcher;
    }
    
    /**
     * Returns true if the $mm is published. ( Keep updated with SchemaFilter->getCriteria() )
     * @param MultimediaObject
     * @return boolean
     */
    public function isPublished($mm, $pubChannelCod)
    {
        $hasStatus = $mm->getStatus() == MultimediaObject::STATUS_PUBLISHED;
        $broadcastType = $mm->getBroadcast()->getBroadcastTypeId();
        $hasBroadcast = $broadcastType  == Broadcast::BROADCAST_TYPE_PUB || $broadcastType == Broadcast::BROADCAST_TYPE_COR;
        $hasPubChannel = $mm->containsTagWithCod($pubChannelCod);

        return $hasStatus && $hasBroadcast && $hasPubChannel;
    }

    /**
     * Returns true if the $mm is hidden. Not 404 on its magic url. ( Keep updated with MultimediaObjectController:magicIndexAction )
     * @param MultimediaObject
     * @param Publication channel code
     * @return boolean
     */
    public function isHidden($mm, $pubChannelCod)
    {
        $hasStatus = in_array($mm->getStatus(), array(MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_HIDE));
        $hasPubChannel = $mm->containsTagWithCod($pubChannelCod);

        return $hasStatus && $hasPubChannel;
    }

    /**
     * Returns true if the $mm has a playable resource. ( Keep updated with SchemaFilter->getCriteria() )
     * @param MultimediaObject
     * @return boolean
     */
    public function hasPlayableResource($mm){
        return $mm->getFilteredTracksWithTags(['display']) || $mm->getProperty('opencast');
    }

    /**
     * Returns true if the $mm is being displayed on the baseplayer. ( Keep updated with SchemaFilter->getCriteria() )
     * @param MultimediaObject
     * @param String
     * @return boolean
     */
    public function canBeDisplayed($mm, $pubChannelCod){
        return $this->isPublished($mm, $pubChannelCod) && $this->hasPlayableResource($mm);
    }
    
    /**
     * Resets the magic url for a given multimedia object. Returns the secret id.
     *
     * @param MultimediaObject
     * @return String
     */
    public function resetMagicUrl($mm){
        $mm->resetSecret();
        $this->dm->persist($mm);
        $this->dm->flush();
        return $mm->getSecret();
    }
    
    /**
     * Update multimedia object
     *
     * @param MultimediaObject $multimediaObject
     * @return MultimediaObject
     */
    public function updateMultimediaObject(MultimediaObject $multimediaObject)
    {
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->dispatcher->dispatchUpdate($multimediaObject);

        return $multimediaObject;
    }



    public function onMultimediaObjectViewed(ViewedEvent $event)
    {
        $track = $event->getTrack();
        $multimediaObject = $event->getMultimediaObject();

        $multimediaObject->incNumview();
        $track && $track->incNumview();

        $this->updateMultimediaObject($multimediaObject);
    }

    /**
     * Inc num view of multimedia object
     *
     * @param MultimediaObject $multimediaObject
     */
    public function incNumView(MultimediaObject $multimediaObject)
    {
        $multimediaObject->incNumview();
        $this->updateMultimediaObject($multimediaObject);
    }

    /**
     * Add  group to multimediaObject
     *
     * @param Group $group
     * @param MultimediaObject $multimediaObject
     * @param boolean $executeFlush
     */
    public function addGroup(Group $group, MultimediaObject $multimediaObject, $executeFlush = true)
    {
        if (!$multimediaObject->containsGroup($group)) {
            // TODO: develop function
            /* if (!$this->isAllowedToModifyMultimediaObjectGroup($multimediaObject, $group)) { */
            /*     throw new \Exception('Not allowed to add  group "'.$group->getKey().'" to multimediaObject "'.$multimediaObject->getMultimediaObjectname().'".'); */
            /* } */
            $multimediaObject->addGroup($group);
            $this->dm->persist($multimediaObject);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($multimediaObject);
        }
    }

    /**
     * Delete  group to multimediaObject
     *
     * @param Group $group
     * @param MultimediaObject $multimediaObject
     * @param boolean $executeFlush
     */
    public function deleteGroup(Group $group, MultimediaObject $multimediaObject, $executeFlush = true)
    {
        if ($multimediaObject->containsGroup($group)) {
            // TODO: develop function
            /* if (!$this->isAllowedToModifyMultimediaObjectGroup($multimediaObject, $group)) { */
            /*     throw new \Exception('Not allowed to delete  group "'.$group->getKey().'" from multimediaObject "'.$multimediaObject->getMultimediaObjectname().'".'); */
            /* } */
            $multimediaObject->removeGroup($group);
            $this->dm->persist($multimediaObject);
            if ($executeFlush) {
                $this->dm->flush();
            }
            $this->dispatcher->dispatchUpdate($multimediaObject);
        }
    }

}

