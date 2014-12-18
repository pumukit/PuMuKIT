<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class TagService
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

  /**
   * Add Tag to Multimedia Object
   *
   * @param MultimediaObject $mmobj
   * @param string $tagId
   * @return MultimediaObject $mmobj
   */
  public function addTagToMultimediaObject(MultimediaObject $mmobj, $tagId)
  {
      $repository = $this->dm->getRepository('PumukitSchemaBundle:Tag');
      $tag = $repository->find($tagId);
      if (null !== $tag) {
          $node = $tag;
          do {
              if (!($mmobj->containsTag($node))) {
                  $mmobj->addTag($node);
                  $node->increaseNumberMultimediaObjects();
                  $this->dm->persist($node);
              }
              $aux = $node->getParent();
              if (null !== $aux) {
                  $node = $aux;
              } else {
                  // TODO throw exception tag tree broken or without ROOT
        break;
              }
          } while (0 !== strcmp($node->getCod(), 'ROOT'));

          $this->dm->persist($mmobj);
          $this->dm->flush();
      } else {
          // TODO throw exception tag not found
      return $mmobj;
      }

      return $mmobj;
  }

  /**
   * Remove Tag from Multimedia Object
   */
  public function removeTagFromMultimediaObject(MultimediaObject $mmobj, $tagId)
  {
      $repository = $this->dm->getRepository('PumukitSchemaBundle:Tag');
      $tag = $repository->find($tagId);
      if (null !== $tag) {
          $node = $tag;
          do {
              $children = $node->getChildren();
              if (!($mmobj->containsAnyTag($children->toArray()))) {
                  $mmobj->removeTag($node);
                  $node->decreaseNumberMultimediaObjects();
                  $this->dm->persist($node);
              }
              $aux = $node->getParent();
              if (null !== $aux) {
                  $node = $aux;
              } else {
                  // TODO throw exception tag tree broken or without ROOT
        break;
              }
          } while (0 !== strcmp($node->getCod(), 'ROOT'));
          $this->dm->persist($mmobj);
          $this->dm->flush();
      } else {
          // TODO throw exception tag not found
      return $mmobj;
      }

      return $mmobj;
  }
}
