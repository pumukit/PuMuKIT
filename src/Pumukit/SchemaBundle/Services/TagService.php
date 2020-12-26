<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\TagInterface;

class TagService
{
    private $dm;
    private $repository;
    private $dispatcher;

    public function __construct(DocumentManager $documentManager, MultimediaObjectEventDispatcherService $dispatcher)
    {
        $this->dm = $documentManager;
        $this->repository = $this->dm->getRepository(Tag::class);
        $this->dispatcher = $dispatcher;
    }

    /**
     * Add Tag to Multimedia Object.
     *
     * @param string $tagId
     * @param bool   $executeFlush
     *
     *@throws \Exception
     *
     * @return array[Tag] addded tags
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
     * @param string $tagCod
     * @param bool   $executeFlush
     *
     * @throws \Exception
     *
     * @return array[Tag] addded tags
     */
    public function addTagByCodToMultimediaObject(MultimediaObject $mmobj, $tagCod, $executeFlush = true)
    {
        $tag = $this->repository->findOneByCod($tagCod);
        if (!$tag) {
            throw new \Exception('Tag'.$tagCod.' not found.');
        }

        return $this->addTag($mmobj, $tag, $executeFlush);
    }

    /**
     * Add Tag to Multimedia Object.
     *
     * @param bool $executeFlush
     *
     * @return array[Tag] addded tags
     */
    public function addTag(MultimediaObject $mmobj, TagInterface $tag, $executeFlush = true)
    {
        $tagAdded = [];

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
            $this->dispatcher->dispatchUpdate($mmobj);
        }

        return $tagAdded;
    }

    /**
     * Remove Tag from Multimedia Object.
     *
     * @param string $tagId
     * @param bool   $executeFlush
     *
     * @throws \Exception
     *
     * @return array[Tag] removed tags
     */
    public function removeTagFromMultimediaObject(MultimediaObject $mmobj, $tagId, $executeFlush = true)
    {
        $tag = $this->repository->find($tagId);
        if (!$tag) {
            throw new \Exception('Tag with id '.$tagId.' not found.');
        }

        return $this->removeTag($mmobj, $tag, $executeFlush);
    }

    /**
     * Remove Tag from Multimedia Object.
     *
     * @param bool $executeFlush
     *
     * @return array[Tag] removed tags
     */
    public function removeTag(MultimediaObject $mmobj, TagInterface $tag, $executeFlush = true)
    {
        $removeTags = [];

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
            $this->dispatcher->dispatchUpdate($mmobj);
        }

        return $removeTags;
    }

    /**
     * Remove one Tag from Multimedia Object.
     *
     * @param string $tagId
     * @param bool   $executeFlush
     *
     * @throws \Exception
     *
     * @return array[Tag] removed tags
     */
    public function removeOneTagFromMultimediaObject(MultimediaObject $mmobj, $tagId, $executeFlush = true)
    {
        $tag = $this->repository->find($tagId);
        if (!$tag) {
            throw new \Exception('Tag with id '.$tagId.' not found.');
        }

        return $this->removeOneTag($mmobj, $tag, $executeFlush);
    }

    /**
     * Remove one Tag from Multimedia Object.
     *
     * @param bool $executeFlush
     *
     * @return array[Tag] removed tags
     */
    public function removeOneTag(MultimediaObject $mmobj, TagInterface $tag, $executeFlush = true)
    {
        $removed = $mmobj->removeTag($tag);
        if ($removed && !$mmobj->isPrototype()) {
            $tag->decreaseNumberMultimediaObjects();
        }

        if (!$removed) {
            return [];
        }

        $this->dm->persist($tag);
        $this->dm->persist($mmobj);

        if ($executeFlush) {
            $this->dm->flush();
            $this->dispatcher->dispatchUpdate($mmobj);
        }

        return [$tag];
    }

    /**
     * Update Tag.
     */
    public function updateTag(TagInterface $tag)
    {
        $tag = $this->saveTag($tag);

        $qb = $this->dm->createQueryBuilder(MultimediaObject::class);

        $query = $qb
            ->updateMany()
            ->field('tags._id')->equals(new ObjectId($tag->getId()))
            ->field('tags.$.title')->set($tag->getI18nTitle())
            ->field('tags.$.description')->set($tag->getI18nDescription())
            ->field('tags.$.cod')->set($tag->getCod())
            ->field('tags.$.metatag')->set($tag->getMetatag())
            ->field('tags.$.display')->set($tag->getDisplay())
            ->field('tags.$.updated')->set($tag->getUpdated())
            ->field('tags.$.slug')->set($tag->getSlug())
            ->field('tags.$.path')->set($tag->getPath())
            ->field('tags.$.level')->set($tag->getLevel())
            ->getQuery()
        ;
        $query->execute();

        $this->dm->flush();

        return $tag;
    }

    /**
     * Save Tag.
     */
    public function saveTag(TagInterface $tag)
    {
        $tag->setUpdated(new \DateTime());

        $this->dm->persist($tag);
        $this->dm->flush();

        return $tag;
    }

    /**
     * Delete Tag.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function deleteTag(TagInterface $tag)
    {
        if ($this->canDeleteTag($tag)) {
            $this->dm->clear('Pumukit\SchemaBundle\Document\MultimediaObject');
            $qb = $this->dm->createQueryBuilder(MultimediaObject::class);

            $query = $qb
                ->updateMany()
                ->field('tags')->pull($qb->expr()->field('_id')->equals($tag->getId()))
                ->getQuery()
            ;
            $query->execute();

            $this->dm->remove($tag);
            $this->dm->flush();

            return true;
        }

        throw new \Exception('Tag with id '.$tag->getId().' can not be deleted.');
    }

    /**
     * Delete Tag.
     *
     * @return bool
     */
    public function canDeleteTag(TagInterface $tag)
    {
        return (bool) ((0 == count($tag->getChildren())) && (0 == $tag->getNumberMultimediaObjects()));
    }

    /**
     * Reset the tags of an array of MultimediaObjects.
     * Deleting all the tag of MultimediaObjects and setting the parameter tags.
     *
     * @param MultimediaObject[] $mmobjs
     * @param Tag[]              $tags
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
            $this->dispatcher->dispatchUpdate($mmobj);
        }

        $this->dm->flush();
    }

    /**
     * Reset the descendent tags of an array of MultimediaObjects and set the target.
     *
     * @param MultimediaObject[] $mmobjs
     * @param Tag[]              $newTags
     * @param Tag[]              $parentTags
     */
    public function syncTagsForCollections(array $mmobjs, array $newTags, array $parentTags)
    {
        foreach ($mmobjs as $mmobj) {
            foreach ($parentTags as $tag) {
                $this->syncTags($mmobj, $newTags, $tag, false);
            }
        }

        $this->dm->flush();

        foreach ($mmobjs as $mmobj) {
            $this->dispatcher->dispatchUpdate($mmobj);
        }
    }

    /**
     * Reset the descendent tags of an array of MultimediaObjects and set the target.
     *
     * @param mixed $executeFlush
     */
    public function syncTags(MultimediaObject $mmobj, array $newTags, TagInterface $parentTag, $executeFlush = true)
    {
        foreach ($mmobj->getTags() as $originalEmbeddedTag) {
            if (!$originalEmbeddedTag->equalsOrDescendantOf($parentTag)) {
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
            if (!$newEmbeddedTag->equalsOrDescendantOf($parentTag)) {
                continue;
            }
            $mmobj->addTag($newEmbeddedTag);
            if (!$mmobj->isPrototype()) {
                $tag = $this->repository->find($newEmbeddedTag->getId());
                $tag->increaseNumberMultimediaObjects();
                $this->dm->persist($tag);
            }
        }

        if ($executeFlush) {
            $this->dispatcher->dispatchUpdate($mmobj);
            $this->dm->flush();
        }
    }

    /**
     * Resets only the 'Categories' tags. Those are all except for the 'PUBCHANNEL' and 'PUBDECISION' tags.
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    public function resetCategoriesForCollections(array $mmobjs, array $newTags)
    {
        foreach ($mmobjs as $mmobj) {
            $this->resetCategories($mmobj, $newTags, false);
        }

        $this->dm->flush();

        foreach ($mmobjs as $mmobj) {
            $this->dispatcher->dispatchUpdate($mmobj);
        }
    }

    /**
     * Resets only the 'Categories' tags. Those are all except for the 'PUBCHANNEL' and 'PUBDECISION' tags.
     *
     * @param bool $executeFlush
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    public function resetCategories(MultimediaObject $mmobj, array $newTags, $executeFlush = true)
    {
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

        if ($executeFlush) {
            $this->dispatcher->dispatchUpdate($mmobj);
            $this->dm->flush();
        }
    }
}
