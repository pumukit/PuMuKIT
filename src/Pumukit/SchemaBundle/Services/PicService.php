<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class PicService
{
  /**
   * Search for resource with id
   */
  public function getResource($controller, $resource_name, $id)
  {
      $dm = $controller->get('doctrine_mongodb')->getManager();
      $repository = $dm->getRepository('PumukitSchemaBundle:'.ucwords($resource_name));
      $resource = $repository->find($id);

      return $resource;
  }

  /**
   * Get pics from series or multimedia object
   */
  public function getPics($controller, $resource_name, $id, $page)
  {
      $dm = $controller->get('doctrine_mongodb')->getManager();
      $repository = $dm->getRepository('PumukitSchemaBundle:'.ucwords($resource_name));

      $limit = 12;
      $offset = ($page - 1) * $limit;

      // TODO
      if (0 == strcmp('Series', ucwords($resource_name))){
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
      } elseif (0 == strcmp('MultimediaObject', ucword($resource_name))) {
	// MultimediaObject: last used pics or pics from video
	$array_pics = array();
	$total = 0;
      }

      $adapter = new ArrayAdapter($array_pics);
      $pics = new Pagerfanta($adapter);

      return array($pics, $total);
  }

}
