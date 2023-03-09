<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation as Serializer;
use MongoDB\BSON\ObjectId;

/**
 * @MongoDB\EmbeddedDocument
 */
class Playlist
{
    /**
     * @MongoDB\ReferenceMany(targetDocument=MultimediaObject::class, storeAs="id", strategy="setArray")
     *
     * @Serializer\Exclude
     */
    private $multimedia_objects;

    public function __construct()
    {
        $this->multimedia_objects = new ArrayCollection();
    }

    public function containsMultimediaObject(MultimediaObject $multimedia_object): bool
    {
        return $this->multimedia_objects->contains($multimedia_object);
    }

    public function addMultimediaObject(MultimediaObject $multimedia_object): void
    {
        $this->multimedia_objects->add($multimedia_object);
    }

    public function removeMultimediaObject(MultimediaObject $multimedia_object): void
    {
        $this->multimedia_objects->removeElement($multimedia_object);
    }

    public function removeAllMultimediaObjectsById($mmobjId): void
    {
        foreach ($this->multimedia_objects as $key => $mmobj) {
            if ($mmobj->getId() === $mmobjId) {
                $this->multimedia_objects->remove($key);
            }
        }
    }

    public function removeMultimediaObjectByPos($pos): void
    {
        $this->multimedia_objects->remove($pos);
    }

    public function getMultimediaObjects()
    {
        return $this->multimedia_objects;
    }

    public function getPublishedMultimediaObjects(): array
    {
        return $this->getMultimediaObjectsByStatus([MultimediaObject::STATUS_PUBLISHED]);
    }

    public function getPublishedAndHiddenMultimediaObjects(): array
    {
        return $this->getMultimediaObjectsByStatus([MultimediaObject::STATUS_HIDDEN, MultimediaObject::STATUS_PUBLISHED]);
    }

    public function getMultimediaObjectsByStatus(array $status = []): array
    {
        if (empty($status)) {
            $status = [MultimediaObject::STATUS_HIDDEN, MultimediaObject::STATUS_PUBLISHED, MultimediaObject::STATUS_BLOCKED];
        }

        $multimediaObjects = [];
        foreach ($this->multimedia_objects as $multimediaObject) {
            try {
                if (in_array(MultimediaObject::STATUS_PUBLISHED, $status, true) && $multimediaObject->isPublished()) {
                    $multimediaObjects[] = $multimediaObject;
                } elseif (in_array(MultimediaObject::STATUS_HIDDEN, $status, true) && $multimediaObject->isHidden()) {
                    $multimediaObjects[] = $multimediaObject;
                } elseif (in_array(MultimediaObject::STATUS_BLOCKED, $status, true) && $multimediaObject->isBlocked()) {
                    $multimediaObjects[] = $multimediaObject;
                }
            } catch (\Exception $exception) {
                continue;
            }
        }

        return $multimediaObjects;
    }

    public function getMultimediaObjectsIdList(): array
    {
        return array_map(
            static function (MultimediaObject $m) {
                return new ObjectId($m->getId());
            },
            $this->multimedia_objects->toArray()
        );
    }

    public function moveMultimediaObject($posStart, $posEnd): bool
    {
        $maxPos = $this->multimedia_objects->count();
        if ($maxPos < 1) {
            return false;
        }
        if (0 === $posStart - $posEnd
           || $posStart < 0 || $posStart > $maxPos) {
            return false; // If start is out of range or start/end is the same, do nothing.
        }
        $posEnd %= $maxPos; // Out of bounds.
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

        return true;
    }
}
