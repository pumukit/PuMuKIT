<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Pagerfanta;

class MultimediaObjectController extends SortableAdminController
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

      $dm = $this->get('doctrine_mongodb.odm.document_manager');
      $repository = $dm->getRepository('PumukitSchemaBundle:Series');
      $series = $repository->find($request->get('id'));

      $page = $this->get('session')->get('admin/mms/page', 1);

      $coll_mms = $series->getMultimediaObjects();
      
      $adapter = new DoctrineCollectionAdapter($coll_mms);
      $mms = new Pagerfanta($adapter);
      
      $mms
	->setCurrentPage($page, true, true)
	->setMaxPerPage(12)
	;
      
      $view = $this
	->view()
	->setTemplate($config->getTemplate('index.html'))
	->setData(array(
			$pluralName => $resources,
			'series' => $series,
			'mms' => $mms
			))
	;
      
      return $this->handleView($view);
  }


  /**
   * Create new resource
   */
  public function createAction(Request $request)
  {
      $config = $this->getConfiguration();
      $pluralName = $config->getPluralResourceName();

      $dm = $this->get('doctrine_mongodb.odm.document_manager');
      $repository = $dm->getRepository('PumukitSchemaBundle:Series');
      $series = $repository->find($request->attributes->get('id'));

      $factory = $this->get('pumukitschema.factory');
      $factory->createMultimediaObject($series);

      $this->setFlash('success', 'create');

      $criteria = $this->getCriteria($config);
      $resources = $this->getResources($request, $config, $criteria);

      $view = $this
      ->view()
      ->setTemplate($config->getTemplate('list.html'))
      ->setTemplateVar($pluralName)
      ->setData($resources)
      ;

      return $this->handleView($view);
  }

  /**
   * Overwrite to update the session.
   */
  public function showAction()
  {
    $config = $this->getConfiguration();
    $data = $this->findOr404();

    $this->get('session')->set('admin/mms/id', $data->getId());

    $dm = $this->get('doctrine_mongodb.odm.document_manager');
    $repository = $dm->getRepository('PumukitSchemaBundle:Role');
    $roles = $repository->findAll();

    $view = $this
      ->view()
      ->setTemplate($config->getTemplate('show.html'))
      ->setData(array(
		      'mm' => $data,
		      'roles' => $roles
		      ))
      ;

    return $this->handleView($view);
  }

  
}