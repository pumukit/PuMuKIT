<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;

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

    $mms = $this->getListMultimediaObjects($series);

    $view = $this
      ->view()
      ->setTemplate($config->getTemplate('index.html'))
      ->setData(array(
                      'series' => $series,
                      'mms' => $mms,
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
    $mmobj = $factory->createMultimediaObject($series);

    $this->addFlash('success', 'create');

    $mms = $this->getListMultimediaObjects($series);

    $view = $this
      ->view()
      ->setTemplate($config->getTemplate('list.html'))
      ->setData(array(
                      'series' => $series,
                      'mms' => $mms,
                      ))
      ;

    return $this->handleView($view);
  }

  /**
   * Overwrite to update the session.
   */
  public function showAction(Request $request)
  {
    $config = $this->getConfiguration();
    $data = $this->findOr404($request);

    $this->get('session')->set('admin/mms/id', $data->getId());

    $roles = $this->getRoles();

    $view = $this
      ->view()
      ->setTemplate($config->getTemplate('show.html'))
      ->setData(array(
                      'mm' => $data,
                      'roles' => $roles,
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

    $resource = $this->findOr404($request);

    $formMeta = $this->createForm($config->getFormType().'_meta', $resource);
    $formPub = $this->createForm($config->getFormType().'_pub', $resource);

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
                      'parent_tags'   => $parentTags,
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

    $resource = $this->findOr404($request);

    $formMeta = $this->createForm($config->getFormType().'_meta', $resource);
    $formPub = $this->createForm($config->getFormType().'_pub', $resource);

    $pubChannelsTags = $this->getTagsByCod('PUBCHANNELS', true);
    $pubDecisionsTags = $this->getTagsByCod('PUBDECISIONS', true);

    $method = $request->getMethod();
    if (in_array($method, array('POST', 'PUT', 'PATCH')) &&
        $formMeta->submit($request, !$request->isMethod('PATCH'))->isValid()) {
      $this->domainManager->update($resource);

      if ($config->isApiRequest()) {
        return $this->handleView($this->view($formMeta));
      }

      /*
        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);
      */

      $mms = $this->getListMultimediaObjects($series);

      $view = $this
        ->view()
        ->setTemplate($this->getConfiguration()->getTemplate('list.html'))
        ->setData(array(
                        'series' => $series,
                        'mms' => $mms,
                        ))
        ;

      return $this->handleView($view);
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
                      'parent_tags'   => $parentTags,
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

    $resource = $this->findOr404($request);

    $formMeta = $this->createForm($config->getFormType().'_meta', $resource);
    $formPub = $this->createForm($config->getFormType().'_pub', $resource);

    $pubChannelsTags = $this->getTagsByCod('PUBCHANNELS', true);
    $pubDecisionsTags = $this->getTagsByCod('PUBDECISIONS', true);

    $method = $request->getMethod();
    if (in_array($method, array('POST', 'PUT', 'PATCH')) &&
        $formPub->submit($request, !$request->isMethod('PATCH'))->isValid()) {
      $resource = $this->updateTags($request->get('pub_channels', null), "PUCH", $resource);
      $resource = $this->updateTags($request->get('pub_decisions', null), "PUDE", $resource);

      $this->domainManager->update($resource);

      if ($config->isApiRequest()) {
        return $this->handleView($this->view($formPub));
      }

      $mms = $this->getListMultimediaObjects($series);

      $view = $this
        ->view()
        ->setTemplate($this->getConfiguration()->getTemplate('list.html'))
        ->setData(array(
                        'series' => $series,
                        'mms' => $mms,
                        ))
        ;

      return $this->handleView($view);
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
                      'parent_tags'   => $parentTags,
                      ))
      ;

    return $this->handleView($view);
  }


  /**
   * 
   */
  public function getChildrenTagAction(Tag $tag, Request $request)
  {
    $config = $this->getConfiguration();

    $view = $this
      ->view()
      ->setTemplate($config->getTemplate('listtagsajax.html'))
      ->setData(array(
                      'nodes' => $tag->getChildren(),
                      'parent' => $tag->getId(),
                      'mmId' => $request->get('mm_id'),
                      'block_tag' => $request->get('tag_id'),
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

    $resource = $this->findOr404($request);

    $tagService = $this->get('pumukitschema.tag');

    $addedTags = $tagService->addTagToMultimediaObject($resource, $request->get('tagId'));

    $json = array('added' => array(), 'recommended' => array());
    foreach($addedTags as $n){
      $json['added'][] = array(
                               'id' => $n->getId(),
                               'cod' => $n->getCod(),
                               'name' => $n->getTitle(),
                               'group' => $n->getPath()
                               );
    }

    return new JsonResponse($json);
  }

  /**
   * Get series
   */
  public function getSeries(Request $request)
  {
    $dm = $this->get('doctrine_mongodb.odm.document_manager');
    $repository = $dm->getRepository('PumukitSchemaBundle:Series');

    if ($this->get('session')->get('admin/series/id', null)) {
      $series = $repository->find($this->get('session')->get('admin/series/id'));
    } else {
      $series = $repository->find($request->get('id'));
      $this->get('session')->set('admin/series/id', $series->getId());
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

    $tags = $repository->findOneByCod($cod);

    if ($tags && $getChildren) {
      return $tags->getChildren();
    }

    return $tags;
  }

  /**
   * Update Tags in Multimedia Object from form
   */
  private function updateTags($checkedTags, $codStart, $resource)
  {
    if (null !== $checkedTags) {
      foreach ($resource->getTags() as $tag) {
        if ((0 == strpos($tag->getCod(), $codStart)) && (false !== strpos($tag->getCod(), $codStart)) && (!in_array($tag->getCod(), $checkedTags))) {
          $resource->removeTag($tag);
        }
      }
      foreach ($checkedTags as $cod => $checked) {
        $tag = $this->getTagsByCod($cod, false);
        $resource->addTag($tag);
      }
    } else {
      foreach ($resource->getTags() as $tag) {
        if ((0 == strpos($tag->getCod(), $codStart)) && (false !== strpos($tag->getCod(), $codStart))) {
          $resource->removeTag($tag);
        }
      }
    }

    return $resource;
  }

  /**
   * Get the view list of multimedia objects
   * belonging to a series
   */
  private function getListMultimediaObjects(Series $series)
  {
    $page = $this->get('session')->get('admin/mms/page', 1);

    $coll_mms = $series->getMultimediaObjects();

    $adapter = new DoctrineCollectionAdapter($coll_mms);
    $mms = new Pagerfanta($adapter);

    $mms
      ->setCurrentPage($page, true, true)
      ->setMaxPerPage(12)
      ;

    // TODO get criteria

    return $mms;
  }
}
