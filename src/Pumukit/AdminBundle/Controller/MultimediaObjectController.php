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

      $series = $this->getSeries($request);

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

      $series = $this->getSeries($request);

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

    $roles = $this->getRoles();

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

  // TODO
  /**
   * Display the form for editing or update the resource.
   */
  public function updateAction(Request $request)
  {
      $config = $this->getConfiguration();
      // TODO VALIDATE SERIES and roles
      $series = $this->getSeries($request);
      $roles = $this->getRoles();

      $resource = $this->findOr404();
      $form = $this->getForm($resource);
      
      if (($request->isMethod('PUT') || $request->isMethod('POST') || $request->isMethod('DELETE')) && $form->bind($request)->isValid()) {
          $event = $this->update($resource);
          if (!$event->isStopped()) {
              $this->setFlash('success', 'update');

	      $criteria = $this->getCriteria($config);
	      $resources = $this->getResources($request, $config, $criteria);	      

	      $pluralName = $config->getPluralResourceName();
	      
	      $view = $this
		->view()
		->setTemplate($config->getTemplate('list.html'))
		->setTemplateVar($pluralName)
		->setData($resources)
		;
	      
	      return $this->handleView($view);
          }

          $this->setFlash($event->getMessageType(), $event->getMessage(), $event->getMessageParams());
      }

      if ($config->isApiRequest()) {
          return $this->handleView($this->view($form));
      }

      $view = $this
      ->view()
      ->setTemplate($config->getTemplate('update.html'))
      ->setData(array(
              'mm'     => $resource,
              'form'   => $form->createView(),
	      'series' => $series,
	      'roles'  => $roles
              ))
      ;

      return $this->handleView($view);
  }
  
  /**
   * Get series
   */
  private function getSeries(Request $request)
  {
      $dm = $this->get('doctrine_mongodb.odm.document_manager');
      $repository = $dm->getRepository('PumukitSchemaBundle:Series');

      if ($this->get('session')->get('admin/series/id', null)){
	$series = $repository->find($this->get('session')->get('admin/series/id'));
      }else{
	$series = $repository->find($request->get('id'));
	$this->get('session')->set('admin/series/id', $request->get('id'));
      }

      return $series;
  }

  /**
   * Get all roles
   */
  private function getRoles()
  {
    $dm = $this->get('doctrine_mongodb.odm.document_manager');
    $repository = $dm->getRepository('PumukitSchemaBundle:Role');
    $roles = $repository->findAll();

    return $roles;
  }
}