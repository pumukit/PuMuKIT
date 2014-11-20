<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class MultimediaObjectController extends AdminController
{
  /**
   * Overwrite to search criteria with date
   */
  public function indexAction(Request $request)
  {
      $config = $this->getConfiguration();

      $criteria = $this->getCriteria($config);
      $resources = $this->getResources($request, $config, $criteria);

      $pluralName = $config->getPluralResourceName();

      $dm = $this->get('doctrine_mongodb')->getManager();
      $repository = $dm->getRepository('PumukitSchemaBundle:Series');
      $series = $repository->find($request->get('id'));

      $view = $this
	->view()
	->setTemplate($config->getTemplate('index.html'))
	->setData(array(
			$pluralName => $resources,
			'series' => $series
			))
	;
      
      return $this->handleView($view);
  }
  
}