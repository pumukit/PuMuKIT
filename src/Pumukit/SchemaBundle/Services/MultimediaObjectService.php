<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Finder\Finder;

class MultimediaObjectService
{
    private $dm;
    private $repo;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
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
     * Returns true if the $mm has a playable resource. ( Keep updated with SchemaFilter->getCriteria() )
     * @param MultimediaObject
     * @return boolean
     */
    public function hasPlayableResource($mm){
        return $mm->getFilteredTracksWithTags(['display']) || $mm->getProperty('opencast');
    }

    /**
     * Returns true if the $mm is being displayed on the webtv frontend. ( Keep updated with SchemaFilter->getCriteria() )
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
    
}