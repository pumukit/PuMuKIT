<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesPic;
use Pumukit\SchemaBundle\Document\MultimediaObject;

use Doctrine\ODM\MongoDB\DocumentManager;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

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
  public function getResource($resource_name, $id)
  {
      $repository = $this->dm->getRepository('PumukitSchemaBundle:'.$resource_name);
      $resource = $repository->find($id);

      return $resource;
  }

  /**
   * Get pics from series or multimedia object
   */
  public function getPics($resource_name, $id, $page)
  {
      $repository = $this->dm->getRepository('PumukitSchemaBundle:'.$resource_name);

      $limit = 12;
      $offset = ($page - 1) * $limit;

      // TODO
      if (0 == strcmp('Series', $resource_name)){
	// Series: pics from multimedia objects inside Series
	$series = $repository->find($id);
	$total_pics = array();
	$series_mmobjs = $series->getMultimediaObjects();
	foreach ($series_mmobjs as $mmboj) {
	  array_push($total_pics, $mmobj->getPics());
	}
	$total = count($total_pics);
	// TODO: get last $limit images according to $page

	if (!empty($total_pics)) {
	  $array_pics = array_slice($total_pics, -$offset, $limit);
	}else{
	  $array_pics = array();
	}
      } elseif (0 == strcmp('MultimediaObject', $resource_name)) {
	// MultimediaObject: last used pics or pics from video
	$array_pics = array();
	$total = 0;
      }

      $adapter = new ArrayAdapter($array_pics);
      $pics = new Pagerfanta($adapter);

      $pics
	->setCurrentPage($page, true, true)
	->setMaxPerPage($limit)
	;

      return array($pics, $total);
  }

  /**
   * Set a pic from an url into the series
   */
  public function setPicUrl($resource_name, $resource_id, $pic_url)
  {
    // TODO validate repository, resource, url
    $repository = $this->dm->getRepository('PumukitSchemaBundle:'.$resource_name);
    $resource = $repository->find($resource_id);

    $class = "\\Pumukit\\SchemaBundle\\Document\\" . $resource_name . "Pic";
    $pic = new $class();
    $pic->setUrl($pic_url);    
    $this->dm->persist($pic);
    $this->dm->flush();

    $resource->addPic($pic);
    $this->dm->persist($resource);
    $this->dm->flush();

    return $resource;
  }
}
