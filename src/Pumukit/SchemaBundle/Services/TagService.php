<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Doctrine\ODM\MongoDB\DocumentManager;

use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\EmbeddedTag;
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
   */
  public function addTagToMultimediaObject(MultimediaObject $mmobj, $tagId)
  {
      $repository = $this->dm->getRepository('PumukitSchemaBundle:Tag');
      $tag = $repository->findById($tagId);
      if (null !== $tag){
        $mmobj->addTag($tag);
	$dm->persist($mmobj);
      }
      
      $node = $tag;
      do {
	$node->increaseNumberMultimediaObjects();
	$dm->persist($node);
	$node = $node->getParent();
      } while (0 !== strcmp($this->getParent()->getCod(), 'ROOT'))

      $dm->flush();

      return $mmobj;
  }
}