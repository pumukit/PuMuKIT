<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\EmbeddedTag;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class TagService
{
    private $dm;
    private $repository;
    private $mmobjRepo;
    private $dispatcher;

    public function __construct(DocumentManager $documentManager, MultimediaObjectEventDispatcherService $dispatcher)
    {
        $this->dm = $documentManager;
        $this->repository = $this->dm->getRepository('PumukitSchemaBundle:Tag');
        $this->mmobjRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->dispatcher = $dispatcher;
    }

    /**
     * Add Tag to Multimedia Object.
     *
     * @param MultimediaObject $mmobj
     * @param string           $tagId
     * @param bool             $executeFlush
     *
     * @return Array[Tag] addded tags
     */
    public function addTagToMultimediaObject(MultimediaObject $mmobj, $tagId, $executeFlush = true)
    {
        $tag = $this->repository->find($tagId);
        if (!$tag) {
            throw new \Exception('Tag with id '.$tagId.' not found.');
        }

        return $this->addTag($mmobj, $tag, $executeFlush);
    }

    /**
     * Add Tag to Multimedia Object.
     *
     * @param MultimediaObject $mmobj
     * @param string           $tagCod
     * @param bool             $executeFlush
     *
     * @return Array[Tag] addded tags
     */
    public function addTagByCodToMultimediaObject(MultimediaObject $mmobj, $tagCod, $executeFlush = true)
    {
        $tag = $this->repository->findOneByCod($tagCod);
        if (!$tag) {
            throw new \Exception('Tag with id '.$tagId.' not found.');
        }

        return $this->addTag($mmobj, $tag, $executeFlush);
    }

    /**
     * Add Tag to Multimedia Object.
     *
     * @param MultimediaObject $mmobj
     * @param Tag              $tag
     * @param bool             $executeFlush
     *
     * @return Array[Tag] addded tags
     */
    public function addTag(MultimediaObject $mmobj, Tag $tag, $executeFlush = true)
    {
        $tagAdded = array();

        if ($mmobj->containsTag($tag)) {
            return $tagAdded;
        }

        do {
            if (!$mmobj->containsTag($tag)) {
                $tagAdded[] = $tag;
                $added = $mmobj->addTag($tag);
                if ($added && !$mmobj->isPrototype()) {
                    $tag->increaseNumberMultimediaObjects();
                }
                $this->dm->persist($tag);
            }
        } while ($tag = $tag->getParent());

        $this->dm->persist($mmobj);
        if ($executeFlush) {
            $this->dm->flush();
        }

        $this->dispatcher->dispatchUpdate($mmobj);

        return $tagAdded;
    }

    /**
     * Remove Tag from Multimedia Object.
     *
     * @param MultimediaObject $mmobj
     * @param string           $tagId
     * @param bool             $executeFlush
     *
     * @return Array[Tag] removed tags
     */
    public function removeTagFromMultimediaObject(MultimediaObject $mmobj, $tagId, $executeFlush = true)
    {
        $removeTags = array();

        $tag = $this->repository->find($tagId);
        if (!$tag) {
            throw new \Exception('Tag with id '.$tagId.' not found.');
        }

        do {
            $children = $tag->getChildren();
            if (!($mmobj->containsAnyTag($children->toArray()))) {
                $removeTags[] = $tag;
                $removed = $mmobj->removeTag($tag);
                if ($removed && !$mmobj->isPrototype()) {
                    $tag->decreaseNumberMultimediaObjects();
                }
                $this->dm->persist($tag);
            } else {
                break;
            }
        } while ($tag = $tag->getParent());

        $this->dm->persist($mmobj);
        if ($executeFlush) {
            $this->dm->flush();
        }

        $this->dispatcher->dispatchUpdate($mmobj);

        return $removeTags;
    }

    /**
     * Reset the tags of an array of MultimediaObjects.
     *
     * @param array[MultimediaObject] $mmobjs
     * @param array[string]           $tags
     *
     * @return array[Tag] removed tags
     */
    public function resetTags(array $mmobjs, array $tags)
    {
        foreach ($mmobjs as $mmobj) {
            if (!$mmobj->isPrototype()) {
                foreach ($mmobj->getTags() as $originalEmbeddedTag) {
                    $originalTag = $this->repository->find($originalEmbeddedTag->getId());
                    $originalTag->decreaseNumberMultimediaObjects();
                    $this->dm->persist($originalTag);
                }
            }
            $mmobj->setTags($tags);
            $this->dm->persist($mmobj);
            if (!$mmobj->isPrototype()) {
                foreach ($tags as $embeddedTag) {
                    $tag = $this->repository->find($embeddedTag->getId());
                    $tag->increaseNumberMultimediaObjects();
                    $this->dm->persist($tag);
                }
            }
        }

        $this->dispatcher->dispatchUpdate($mmobj);
        $this->dm->flush();
    }

    /**
     * Update Tag.
     *
     * @param Tag $tag
     *
     * @return Tag
     */
    public function updateTag(Tag $tag)
    {
        $tag = $this->saveTag($tag);

        foreach ($this->mmobjRepo->findAllByTag($tag) as $mmobj) {
            foreach ($mmobj->getTags() as $embeddedTag) {
                if ($tag->getId() === $embeddedTag->getId()) {
                    $embeddedTag = $this->updateEmbeddedTag($tag, $embeddedTag);
                    $this->dm->persist($mmobj);
                }
            }
        }
        $this->dm->flush();

        return $tag;
    }

    /**
     * Save Tag.
     *
     * @param Tag $tag
     *
     * @return Tag
     */
    public function saveTag(Tag $tag)
    {
        $tag->setUpdated(new \Datetime('now'));

        $this->dm->persist($tag);
        $this->dm->flush();

        return $tag;
    }

    /**
     * Delete Tag.
     *
     * @param Tag $tag
     *
     * @return bool
     */
    public function deleteTag(Tag $tag)
    {
        if ($this->canDeleteTag($tag)) {
            $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
            $qb = $this->dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject');

            $query = $qb
                ->update()
                ->multiple(true)
                ->field('tags')->pull($qb->expr()->field('_id')->equals($tag->getId()))
                ->getQuery();
            $aux = $query->execute();

            $this->dm->remove($tag);
            $this->dm->flush();

            return true;
        }

        throw new \Exception('Tag with id '.$tag->getId().' can not be deleted.');
    }

    /**
     * Delete Tag.
     *
     * @param Tag $tag
     *
     * @return bool
     */
    public function canDeleteTag(Tag $tag)
    {
        return (bool) ((0 == count($tag->getChildren())) && (0 == $tag->getNumberMultimediaObjects()));
    }

    /**
     * Update embedded tag.
     *
     * @param Tag         $tag
     * @param EmbeddedTag $embeddedTag
     *
     * @return EmbeddedTag
     */
    private function updateEmbeddedTag(Tag $tag, EmbeddedTag $embeddedTag)
    {
        if (null !== $tag) {
            $embeddedTag->setI18nTitle($tag->getI18nTitle());
            $embeddedTag->setI18nDescription($tag->getI18nDescription());
            $embeddedTag->setSlug($tag->getSlug());
            $embeddedTag->setCod($tag->getCod());
            $embeddedTag->setMetatag($tag->getMetatag());
            $embeddedTag->setDisplay($tag->getDisplay());
            $embeddedTag->setLocale($tag->getLocale());
            $embeddedTag->setSlug($tag->getSlug());
            $embeddedTag->setCreated($tag->getCreated());
            $embeddedTag->setUpdated($tag->getUpdated());
        }

        return $embeddedTag;
    }

    /**
     * Resets only the 'Categories' tags. Those are all except for the 'PUBCHANNEL' and 'PUBDECISION' tags.
     *
     * @param array[MultimediaObject] $mmobjs
     * @param array[string]           $tags
     */
    public function resetCategories(array $mmobjs, array $newTags)
    {
        foreach ($mmobjs as $mmobj) {
            foreach ($mmobj->getTags() as $originalEmbeddedTag) {
                if ($originalEmbeddedTag->isPubTag()) {
                    continue;
                }
                $mmobj->removeTag($originalEmbeddedTag);
                if (!$mmobj->isPrototype()) {
                    $originalTag = $this->repository->find($originalEmbeddedTag->getId());
                    $originalTag->decreaseNumberMultimediaObjects();
                    $this->dm->persist($originalTag);
                }
            }
            foreach ($newTags as $newEmbeddedTag) {
                if ($newEmbeddedTag->isPubTag()) {
                    continue;
                }
                $mmobj->addTag($newEmbeddedTag);
                if (!$mmobj->isPrototype()) {
                    $tag = $this->repository->find($newEmbeddedTag->getId());
                    $tag->increaseNumberMultimediaObjects();
                    $this->dm->persist($tag);
                }
            }

            $this->dm->persist($mmobj);
            $this->dispatcher->dispatchUpdate($mmobj);
            $this->dm->flush(); //May cause performance issues in the future.
        }
    }
}
