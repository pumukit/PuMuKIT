<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class TagService
{
    private $dm;
    private $repository;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repository = $this->dm->getRepository('PumukitSchemaBundle:Tag');
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

        do {
            if (!$mmobj->containsTag($tag)) {
                $tagAdded[] = $tag;
                $mmobj->addTag($tag);
                if (!$mmobj->isPrototype()) {
                  $tag->increaseNumberMultimediaObjects();
                }
                $this->dm->persist($tag);
            }
        } while ($tag = $tag->getParent());

        $this->dm->persist($mmobj);
        if ($executeFlush) {
            $this->dm->flush();
        }

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
              $mmobj->removeTag($tag);
              $tag->decreaseNumberMultimediaObjects();
              $this->dm->persist($tag);
          } else {
              break;
          }
      } while ($tag = $tag->getParent());

      $this->dm->persist($mmobj);
      if ($executeFlush) {
          $this->dm->flush();
      }

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
        foreach($mmobj->getTags() as $originalEmbeddedTag) {
            $originalTag = $this->repository->find($originalEmbeddedTag->getId());
            $originalTag->decreaseNumberMultimediaObjects();
            $this->dm->persist($originalTag);
        }
        $mmobj->setTags($tags);
        $this->dm->persist($mmobj);
        foreach($tags as $embeddedTag) {
            $tag = $this->repository->find($embeddedTag->getId());
            $tag->increaseNumberMultimediaObjects();
            $this->dm->persist($tag);
        }

    }
    
    $this->dm->flush();
  }
  
}
