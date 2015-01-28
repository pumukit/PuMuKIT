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
     * @return Array[Tag]       addded tags
     */
    public function addTagToMultimediaObject(MultimediaObject $mmobj, $tagId)
    {
        $tagAdded = array();

        $tag = $this->repository->find($tagId);
        if (!$tag) {
          // TODO throw exception tag not found #6106
          return $tagAdded;
        }

        do {
            if (!$mmobj->containsTag($tag)) {
                $tagAdded[] = $tag;
                $mmobj->addTag($tag);
                $tag->increaseNumberMultimediaObjects();
                $this->dm->persist($tag);
            }
        } while ($tag = $tag->getParent());

        $this->dm->persist($mmobj);
        $this->dm->flush();

        return $tagAdded;
    }

  /**
   * Remove Tag from Multimedia Object
   *
   * @param MultimediaObject $mmobj
   * @param string $tagId
   * @return Array[Tag] removed tags
   */
  public function removeTagFromMultimediaObject(MultimediaObject $mmobj, $tagId)
  {
      $removeTags = array();

      $tag = $this->repository->find($tagId);
      if (!$tag) {
        //TODO throw exception tag not found #6106
        return $removeTags;
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
      $this->dm->flush();

      return $removeTags;
  }
}
