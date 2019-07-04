<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation as Serializer;

/**
 * Pumukit\SchemaBundle\Document\Playlist.
 *
 * @MongoDB\EmbeddedDocument
 */
class Playlist
{
    /**
     * @var ArrayCollection
     *
     * @MongoDB\ReferenceMany(targetDocument="MultimediaObject", simple=true, strategy="setArray")
     * @Serializer\Exclude
     */
    private $multimedia_objects;

    public function __construct()
    {
        $this->multimedia_objects = new ArrayCollection();
    }

    /**
     * Contains multimedia_object.
     *
     * @param MultimediaObject $multimedia_object
     *
     * @return bool
     */
    public function containsMultimediaObject(MultimediaObject $multimedia_object)
    {
        return $this->multimedia_objects->contains($multimedia_object);
    }

    /**
     * Add multimedia object.
     *
     * @param MultimediaObject $multimedia_object
     */
    public function addMultimediaObject(MultimediaObject $multimedia_object)
    {
        return $this->multimedia_objects->add($multimedia_object);
    }

    /**
     * Remove multimedia object.
     *
     * @param MultimediaObject $multimedia_object
     */
    public function removeMultimediaObject(MultimediaObject $multimedia_object)
    {
        $this->multimedia_objects->removeElement($multimedia_object);
    }

    /**
     * Removes all references to the multimedia objects with the given id.
     *
     * @param MultimediaObject $multimedia_object
     * @param mixed            $mmobjId
     */
    public function removeAllMultimediaObjectsById($mmobjId)
    {
        foreach ($this->multimedia_objects as $key => $mmobj) {
            if ($mmobj->getId() == $mmobjId) {
                $this->multimedia_objects->remove($key);
            }
        }
    }

    /**
     * Remove multimedia object by its position in the playlist.
     *
     * @param int $pos Position (starting from 0) of the mmobj in the playlist
     */
    public function removeMultimediaObjectByPos($pos)
    {
        $this->multimedia_objects->remove($pos);
    }

    /**
     * Get multimedia_objects.
     *
     * @return ArrayCollection
     */
    public function getMultimediaObjects()
    {
        return $this->multimedia_objects;
    }

    /**
     * Get published multimediaObjects.
     *
     * @return array
     */
    public function getPublishedMultimediaObjects()
    {
        return $this->getMultimediaObjectsByStatus([MultimediaObject::STATUS_PUBLISHED]);
    }

    /**
     * Get published and hiddden multimediaObjects.
     *
     * @return array
     */
    public function getPublishedAndHiddenMultimediaObjects()
    {
        return $this->getMultimediaObjectsByStatus([MultimediaObject::STATUS_HIDDEN, MultimediaObject::STATUS_PUBLISHED]);
    }

    /**
     * Get Published mmobjs
     * try catch is used to avoid filter issues.
     * By default, returns all mmobjs (all status).
     *
     * @param array $status
     *
     * @return array
     */
    public function getMultimediaObjectsByStatus(array $status = [])
    {
        if (empty($status)) {
            $status = [MultimediaObject::STATUS_HIDDEN, MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_BLOCKED];
        }

        $multimediaObjects = [];
        foreach ($this->multimedia_objects as $multimediaObject) {
            try {
                if (in_array(MultimediaObject::STATUS_PUBLISHED, $status) && $multimediaObject->isPublished()) {
                    $multimediaObjects[] = $multimediaObject;
                } elseif (in_array(MultimediaObject::STATUS_HIDDEN, $status) && $multimediaObject->isHidden()) {
                    $multimediaObjects[] = $multimediaObject;
                } elseif (in_array(MultimediaObject::STATUS_BLOCKED, $status) && $multimediaObject->isBlocked()) {
                    $multimediaObjects[] = $multimediaObject;
                }
            } catch (\Exception $exception) {
                continue;
            }
        }

        return $multimediaObjects;
    }

    /**
     * Get the mongo id list of multimedia objects.
     *
     * @return ArrayCollection
     */
    public function getMultimediaObjectsIdList()
    {
        $mmobjIds = array_map(
            function ($m) {
                return new \MongoId($m->getId());
            },
            $this->multimedia_objects->toArray()
        );

        return $mmobjIds;
    }

    /**
     * Move multimedia_objects.
     *
     * @param mixed $posStart
     * @param mixed $posEnd
     *
     * @return ArrayCollection
     */
    public function moveMultimediaObject($posStart, $posEnd)
    {
        $maxPos = $this->multimedia_objects->count();
        if ($maxPos < 1) {
            return false;
        }
        if (0 == $posStart - $posEnd
           || $posStart < 0 || $posStart > $maxPos) {
            return false; //If start is out of range or start/end is the same, do nothing.
        }
        $posEnd = $posEnd % $maxPos; //Out of bounds.
        if ($posEnd < 0) {
            $posEnd = $maxPos + $posEnd;
        }
        $tempObject = $this->multimedia_objects->get($posStart);
        if ($posStart - $posEnd > 0) {
            for ($i = $posStart; $i > $posEnd; --$i) {
                $prevObject = $this->multimedia_objects->get($i - 1);
                $this->multimedia_objects->set($i, $prevObject);
            }
        } else {
            for ($i = $posStart; $i < $posEnd; ++$i) {
                $nextObject = $this->multimedia_objects->get($i + 1);
                $this->multimedia_objects->set($i, $nextObject);
            }
        }
        $this->multimedia_objects->set($posEnd, $tempObject);
    }
}
