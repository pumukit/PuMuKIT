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
	      if (null !== $aux){
	          $node = $aux;
	      }else{
	          break;
	          // TODO throw exception tag tree broken or without ROOT
	      }
          } while (0 !== strcmp($node->getCod(), 'ROOT'));

          $this->dm->persist($mmobj);
          $this->dm->flush();
      }else{
	  // TODO throw exception tag not found
      }       

      return $mmobj;
  }
}
