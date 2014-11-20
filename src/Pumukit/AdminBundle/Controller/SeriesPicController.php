<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class SeriesPicController extends ElementController
{
  /**
   * Render create.html
   */
  public function createAction(Request $request)
  {
    $config = $this->getConfiguration();

    if (null != $request->attributes->get('id')) {
      $resource_name = $this->_getResourceName($request);
      $id = $request->attributes->get('id');
      $pic_service = $this->get('pumukitschema.pic');
      $resource = $pic_service->getResource($this, $resource_name, $id);
    }else{
      $resource = null;
    }
	 
    if (null != $resource) {
      //raise error or show message
    }

    // TODO search in picservice according to page (in criteria)
    if ($request->get('page', null)) {
      var_dump($this->get('session'));exit;
      $this->get('session')->set('admin/'.$resource_name.'pic/page', $request->get('page', 1));
    }
    $page = $this->get('session')->get('admin/'.$resource_name.'pic/page', 1);

    list($pics, $total) = $pic_service->getPics($this, $resource_name, $id, $page);

    $pics
      ->setCurrentPage($page, true, true)
      ->setMaxPerPage(12)
      ;
	
    $view = $this
      ->view()
      ->setTemplate($config->getTemplate('create.html'))
      ->setData(array(
		      'resource' => $resource,
		      'resource_name' => $resource_name,
		      'pics' => $pics,
		      'page' => $page,
		      'total' => $total
		      ));

    return $this->handleView($view);
  }

  /**
   * Assign a picture from an url 
   * or from an existing one
   * to the series
   */
  public function updateAction(Request $request)
  {
    $config = $this->getConfiguration();

    if ($request->get('url', null)){
      $resource_name = $this->_getResourceName($request);
      $resource_id = $request->attributes->get('id');
      $pic_service = $this->get('pumukitschema.pic');
      $resource = $pic_service->setPicUrl($this, $resource_name, $resource_id, $request->get('url'));
    }
    
    $view = $this
      ->view()
      ->setTemplate($config->getTemplate('list.html'))
      ->setData(array($resource_name => $resource));

    return $this->handleView($view);
  }
  
  /**
   * Get resource name from route
   */
  private function _getResourceName(Request $request)
  {
    $route = $request->attributes->get('_route');
    $partial_name = split('_', $route);
    $resource_name = split('pic', $partial_name[1])[0];
    
    return $resource_name;
  }
  
}