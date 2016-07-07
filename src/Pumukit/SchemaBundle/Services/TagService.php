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
     * Add Tag to Multimedia Object
     *
     * @param  MultimediaObject $mmobj
     * @param  string           $tagId
     * @param  boolean          $executeFlush
     * @return Array[Tag]       addded tags
     */
    public function addTagToMultimediaObject(MultimediaObject $mmobj, $tagId, $executeFlush=true)
    {
        $tagAdded = array();

        $tag = $this->repository->find($tagId);
        if (!$tag) {
            throw new \Exception("Tag with id ".$tagId." not found.");
        }

        if( $mmobj->containsTag($tag)) {
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
     * Remove Tag from Multimedia Object
     *
     * @param MultimediaObject $mmobj
     * @param string $tagId
     * @param  boolean          $executeFlush
     * @return Array[Tag] removed tags
     */
    public function removeTagFromMultimediaObject(MultimediaObject $mmobj, $tagId, $executeFlush=true)
    {
        $removeTags = array();

        $tag = $this->repository->find($tagId);
        if (!$tag) {
            throw new \Exception("Tag with id ".$tagId." not found.");
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
     * Reset the tags of an array of MultimediaObjects
     *
     * @param array[MultimediaObject] $mmobjs
     * @param array[string] $tags
     * @return array[Tag] removed tags
     */
    public function resetTags(array $mmobjs, array $tags)
    {
        $modifyTags = array();

        foreach($mmobjs as $mmobj) {
            if (!$mmobj->isPrototype()) {
                foreach($mmobj->getTags() as $originalEmbeddedTag) {
                    $originalTag = $this->repository->find($originalEmbeddedTag->getId());
                    $originalTag->decreaseNumberMultimediaObjects();
                    $this->dm->persist($originalTag);
                }
            }
            $mmobj->setTags($tags);
            $this->dm->persist($mmobj);
            if (!$mmobj->isPrototype()) {
                foreach($tags as $embeddedTag) {
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
     * Update Tag
     *
     * @param Tag $tag
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
     * Save Tag
     *
     * @param Tag $tag
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
     * Update embedded tag
     *
     * @param  Tag         $tag
     * @param  EmbeddedTag $embeddedTag
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
}
