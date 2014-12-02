<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesPic;
use Pumukit\SchemaBundle\Document\MultimediaObject;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;

class PicService
{
  private $dm;

  public function __construct(DocumentManager $documentManager)
  {
      $this->dm = $documentManager;
  }


  /**
   * Search for resource with id
   */
  public function getResource($resourceName, $id)
  {
      $repository = $this->dm->getRepository('PumukitSchemaBundle:'.$resourceName);
      $resource = $repository->find($id);

      return $resource;
  }

  /**
   * Get pics from series or multimedia object
   */
  public function getPics($resourceName, $id, $page, $limit)
  {
      $repository = $this->dm->getRepository('PumukitSchemaBundle:'.$resourceName);

      $offset = ($page - 1) * $limit;

      $collPics = new ArrayCollection();
      $total = 0;

      // TODO
      if (0 == strcmp('Series', $resourceName)){
	// Series: pics from multimedia objects inside Series
	$series = $repository->find($id);
	$auxFirst = true;
	$seriesMmobjs = $series->getMultimediaObjects();
	foreach ($seriesMmobjs as $mmobj) {
	  if ($auxFirst){
	    $collPics = $mmobj->getPics();
	    $auxFirst = false;
	  }else{
	    foreach ($mmobj->getPics() as $pic) {
	      $collPics->add($pic);
	    }
	  }
	}

	if ($collPics !== null){
	  $total = $collPics->count();
	}

	// TODO: get last $limit images according to $page
	if (0 !== $total) {
	  foreach ($collPics as $index => $pic){
	    if (!in_array($index, range($offset, $limit + $offset - 1))) {
	      $collPics->remove($pic);
	    }
	  }
	}
      } elseif (0 == strcmp('MultimediaObject', $resourceName)) {
	// MultimediaObject: last used pics or pics from video
      }

      return array($collPics, $total);
  }

  /**
   * Set a pic from an url into the series
   */
  public function setPicUrl($resourceName, $resource_id, $picUrl)
  {
    // TODO validate repository, resource, url
    $repository = $this->dm->getRepository('PumukitSchemaBundle:'.$resourceName);
    $resource = $repository->find($resource_id);

    $class = "\\Pumukit\\SchemaBundle\\Document\\" . $resourceName . "Pic";
    $pic = new $class();
    $pic->setUrl($picUrl);    
    $this->dm->persist($pic);
    $this->dm->flush();

    $resource->addPic($pic);
    $this->dm->persist($resource);
    $this->dm->flush();

    return $resource;
  }
}
