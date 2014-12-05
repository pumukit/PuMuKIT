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
  public function editAction(Request $request)
  {
      $config = $this->getConfiguration();

      // TODO VALIDATE SERIES and roles
      $series = $this->getSeries($request);
      $roles = $this->getRoles();
      $parentTags = $this->getParentTags();

      $resource = $this->findOr404();

      $formMeta = $this->createForm($config->getFormType() . '_meta', $resource);
      $formPub = $this->createForm($config->getFormType() . '_pub', $resource);
      
      $pubChannelTags = $this->getTagsByCod('PUBCHANNELS', true);
      $pubDecisionsTags = $this->getTagsByCod('PUBDECISIONS', true);

      $view = $this
      ->view()
      ->setTemplate($config->getTemplate('edit.html'))
      ->setData(array(
              'mm'            => $resource,
              'form_meta'     => $formMeta->createView(),
              'form_pub'      => $formPub->createView(),
	      'series'        => $series,
	      'roles'         => $roles,
	      'pub_channels'  => $pubChannelTags,
	      'pub_decisions' => $pubDecisionsTags,
	      'parent_tags'   => $parentTags
              ))
      ;

      return $this->handleView($view);
  }

  // TODO
  /**
   * Display the form for editing or update the resource.
   */
  public function updatemetaAction(Request $request)
  {
      $config = $this->getConfiguration();

      // TODO VALIDATE SERIES and roles
      $series = $this->getSeries($request);
      $roles = $this->getRoles();
      $parentTags = $this->getParentTags();

      $resource = $this->findOr404();

      $formMeta = $this->createForm($config->getFormType() . '_meta', $resource);
      $formPub = $this->createForm($config->getFormType() . '_pub', $resource);

      $pubChannelsTags = $this->getTagsByCod('PUBCHANNELS', true);
      $pubDecisionsTags = $this->getTagsByCod('PUBDECISIONS', true);

      if (($request->isMethod('PUT') || $request->isMethod('POST') || $request->isMethod('DELETE')) && $formMeta->bind($request)->isValid()) {
	$event = $this->update($resource);
          if (!$event->isStopped()) {
              $this->setFlash('success', 'updatemeta');

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
          return $this->handleView($this->view($formMeta));
      }

      $view = $this
      ->view()
      ->setTemplate($config->getTemplate('edit.html'))
      ->setData(array(
              'mm'            => $resource,
              'form_meta'     => $formMeta->createView(),
              'form_pub'      => $formPub->createView(),
	      'series'        => $series,
	      'roles'         => $roles,
	      'pub_channels'  => $pubChannelsTags,
	      'pub_decisions' => $pubDecisionsTags,
	      'parent_tags'   => $parentTags
              ))
      ;

      return $this->handleView($view);
  }

  // TODO
  /**
   * Display the form for editing or update the resource.
   */
  public function updatepubAction(Request $request)
  {
      $config = $this->getConfiguration();

      // TODO VALIDATE SERIES and roles
      $series = $this->getSeries($request);
      $roles = $this->getRoles();
      $parentTags = $this->getParentTags();

      $resource = $this->findOr404();

      $formMeta = $this->createForm($config->getFormType() . '_meta', $resource);
      $formPub = $this->createForm($config->getFormType() . '_pub', $resource);

      $pubChannelsTags = $this->getTagsByCod('PUBCHANNELS', true);
      $pubDecisionsTags = $this->getTagsByCod('PUBDECISIONS', true);
      
      if (($request->isMethod('PUT') || $request->isMethod('POST') || $request->isMethod('DELETE')) && $formPub->bind($request)->isValid()) {

	$resource = $this->updateTags($request->get('pub_channels', null), "PUCH", $resource);
	$resource = $this->updateTags($request->get('pub_decisions', null), "PUDE", $resource);

	$event = $this->update($resource);
          if (!$event->isStopped()) {
              $this->setFlash('success', 'updatepub');

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
          return $this->handleView($this->view($formPub));
      }

      $view = $this
      ->view()
	->setTemplate($config->getTemplate('edit.html'))
      ->setData(array(
              'mm'            => $resource,
              'form_meta'     => $formMeta->createView(),
              'form_pub'      => $formPub->createView(),
	      'series'        => $series,
	      'roles'         => $roles,
	      'pub_channels'  => $pubChannelsTags,
	      'pub_decisions' => $pubDecisionsTags,
	      'parent_tags'   => $parentTags
              ))
      ;

      return $this->handleView($view);
  }

  /**
   * Add Tag
   */
  public function addTagAction(Request $request)
  {
    $config = $this->getConfiguration();

    $resource = $this->findOr404();

    $tagService = $this->get('pumukitschema.tag');
    $resource = $tagService->addTagToMultimediaObject($resource, $request->get('tagId'));

    return $this->redirectTo($resource);
  }
  
  /**
   * Get series
   */
  public function getSeries(Request $request)
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
  public function getRoles()
  {
    $dm = $this->get('doctrine_mongodb.odm.document_manager');
    $repository = $dm->getRepository('PumukitSchemaBundle:Role');
    $roles = $repository->findAll();

    return $roles;
  }

  /**
   * Get parten tags
   */
  public function getParentTags()
  {
    $dm = $this->get('doctrine_mongodb.odm.document_manager');
    $repository = $dm->getRepository('PumukitSchemaBundle:Tag');
    $parentTags = $repository->findOneByCod('ROOT')->getChildren();

    return $parentTags;
  }

  /**
   * Get tags by cod
   */
  public function getTagsByCod($cod, $getChildren)
  {
    $dm = $this->get('doctrine_mongodb.odm.document_manager');
    $repository = $dm->getRepository('PumukitSchemaBundle:Tag');
    if ($getChildren){
      $tags = $repository->findOneByCod($cod)->getChildren();
    }else{
      $tags = $repository->findOneByCod($cod);
    }
    return $tags;
  }

  /**
   * Update Tags in Multimedia Object from form
   */
  private function updateTags($checkedTags, $codStart, $resource)
  {
    if (null !== $checkedTags){
      foreach ($resource->getTags() as $tag){
	if ((0 == strpos($tag->getCod(), $codStart)) && (false !== strpos($tag->getCod(), $codStart)) && (!in_array($tag->getCod(), $checkedTags))){
	  $resource->removeTag($tag);
	}
      }
      foreach ($checkedTags as $cod => $checked) {
	$tag = $this->getTagsByCod($cod, false);
	$resource->addTag($tag);
      }
    }else{
      foreach ($resource->getTags() as $tag){
	if ((0 == strpos($tag->getCod(), $codStart)) && (false !== strpos($tag->getCod(), $codStart))){
	  $resource->removeTag($tag);
	}
      }
    }

    return $resource;
  }
}