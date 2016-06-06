<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pumukit\SchemaBundle\Document\Playlist
 *
 * @MongoDB\EmbeddedDocument
 */
class Playlist
{
    /**
     * @var ArrayCollection $multimedia_objects
     *
     * @MongoDB\ReferenceMany(targetDocument="MultimediaObject", simple=true)
     * @Serializer\Exclude
     */
    private $multimedia_objects;

    public function __construct()
    {
        $this->multimedia_objects = new ArrayCollection();
    }

    /**
     * Contains multimedia_object
     *
     * @param MultimediaObject $multimedia_object
     *
     * @return boolean
     */
    public function containsMultimediaObject(MultimediaObject $multimedia_object)
    {
        return $this->multimedia_objects->contains($multimedia_object);
    }

    /**
     * Add multimedia object
     *
     * @param MultimediaObject $multimedia_object
     */
    public function addMultimediaObject(MultimediaObject $multimedia_object)
    {
        return $this->multimedia_objects->add($multimedia_object);
    }

    /**
     * Remove multimedia object
     *
     * @param MultimediaObject $multimedia_object
     */
    public function removeMultimediaObject(MultimediaObject $multimedia_object)
    {
        $this->multimedia_objects->removeElement($multimedia_object);
    }


    /**
     * Get multimedia_objects
     *
     * @return ArrayCollection
     */
    public function getMultimediaObjects()
    {
        return $this->multimedia_objects;
    }
}
