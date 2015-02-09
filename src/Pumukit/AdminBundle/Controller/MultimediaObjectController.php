<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\MultimediaObject;

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

       if ((1 === count($resources)) && (null !== $this->get('session')->get('admin/mms/id'))){
           $this->get('session')->remove('admin/mms/id');
       }

       $pluralName = $config->getPluralResourceName();

       $factoryService = $this->get('pumukitschema.factory');

       $sessionId = $this->get('session')->get('admin/series/id', null);
       $series = $factoryService->findSeriesById($request->get('id'), $sessionId);
       $this->get('session')->set('admin/series/id', $series->getId());

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

       $factoryService = $this->get('pumukitschema.factory');

       $sessionId = $this->get('session')->get('admin/series/id', null);
       $series = $factoryService->findSeriesById($request->get('id'), $sessionId);
       $this->get('session')->set('admin/series/id', $series->getId());

       $mmobj = $factoryService->createMultimediaObject($series);

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

      $roles = $this->get('pumukitschema.factory')->getRoles();

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

        $factoryService = $this->get('pumukitschema.factory');
        
        $roles = $factoryService->getRoles();
        if (null === $roles){
            throw new \Exception('Not found any role.');
        }

        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->get('id'), $sessionId);
        if (null === $series){
            throw new \Exception('Series with id '.$request->get('id').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());

        $parentTags = $factoryService->getParentTags();

        $resource = $this->findOr404($request);

        $formMeta = $this->createForm($config->getFormType().'_meta', $resource);
        $formPub = $this->createForm($config->getFormType().'_pub', $resource);

        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $jobs = $this->get('pumukitencoder.job')->getJobsByMultimediaObjectId($resource->getId());
        $jobStatusError = $this->get('pumukitencoder.job')->getStatusError();

        $notMasterProfiles = $this->get('pumukitencoder.profile')->getMasterProfiles(false);

        $template = '';      
        if (MultimediaObject::STATUS_PROTOTYPE === $resource->getStatus()){
            $template = '_template';
        }

        return $this->render('PumukitAdminBundle:MultimediaObject:edit.html.twig',
                             array(
                                   'mm'            => $resource,
                                   'form_meta'     => $formMeta->createView(),
                                   'form_pub'      => $formPub->createView(),
                                   'series'        => $series,
                                   'roles'         => $roles,
                                   'pub_channels'  => $pubChannelsTags,
                                   'pub_decisions' => $pubDecisionsTags,
                                   'parent_tags'   => $parentTags,
                                   'jobs'          => $jobs,
                                   'status_error'  => $jobStatusError,
                                   'not_master_profiles' => $notMasterProfiles,
                                   'template' => $template
                                   )
                             );
    }

    // TODO
    /**
     * Display the form for editing or update the resource.
     */
    public function updatemetaAction(Request $request)
    {
        $config = $this->getConfiguration();

        $factoryService = $this->get('pumukitschema.factory');

        $roles = $factoryService->getRoles();
        if (null === $roles){
            throw new \Exception('Not found any role.');
        }

        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->get('id'), $sessionId);
        if (null === $series){
            throw new \Exception('Series with id '.$request->get('id').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());

        $parentTags = $factoryService->getParentTags();

        $resource = $this->findOr404($request);

        $formMeta = $this->createForm($config->getFormType().'_meta', $resource);
        $formPub = $this->createForm($config->getFormType().'_pub', $resource);

        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

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

          return $this->render('PumukitAdminBundle:MultimediaObject:list.html.twig',
                               array(
                                     'series' => $series,
                                     'mms' => $mms
                                     )
                               );         
        }

        if ($config->isApiRequest()) {
            return $this->handleView($this->view($formMeta));
        }

        return $this->render('PumukitAdminBundle:MultimediaObject:edit.html.twig',
                             array(
                                   'mm'            => $resource,
                                   'form_meta'     => $formMeta->createView(),
                                   'form_pub'      => $formPub->createView(),
                                   'series'        => $series,
                                   'roles'         => $roles,
                                   'pub_channels'  => $pubChannelsTags,
                                   'pub_decisions' => $pubDecisionsTags,
                                   'parent_tags'   => $parentTags
                                   )
                             );
    }
    
    // TODO
    /**
     * Display the form for editing or update the resource.
     */
    public function updatepubAction(Request $request)
    {
        $config = $this->getConfiguration();

        $factoryService = $this->get('pumukitschema.factory');

        $roles = $factoryService->getRoles();
        if (null === $roles){
            throw new \Exception('Not found any role.');
        }

        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->get('id'), $sessionId);
        if (null === $series){
            throw new \Exception('Series with id '.$request->get('id').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());

        $parentTags = $factoryService->getParentTags();

        $resource = $this->findOr404($request);

        $formMeta = $this->createForm($config->getFormType().'_meta', $resource);
        $formPub = $this->createForm($config->getFormType().'_pub', $resource);

        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

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

            return $this->render('PumukitAdminBundle:MultimediaObject:list.html.twig',
                                 array(
                                       'series' => $series,
                                       'mms' => $mms
                                       )
                                 );
        }

        if ($config->isApiRequest()) {
            return $this->handleView($this->view($formPub));
        }

        return $this->render('PumukitAdminBundle:MultimediaObject:edit.html.twig',
                             array(
                                   'mm'            => $resource,
                                   'form_meta'     => $formMeta->createView(),
                                   'form_pub'      => $formPub->createView(),
                                   'series'        => $series,
                                   'roles'         => $roles,
                                   'pub_channels'  => $pubChannelsTags,
                                   'pub_decisions' => $pubDecisionsTags,
                                   'parent_tags'   => $parentTags
                                   )
                             );
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
     * Delete Tag
     */
    public function deleteTagAction(Request $request)
    {
        $config = $this->getConfiguration();

        $resource = $this->findOr404($request);

        $tagService = $this->get('pumukitschema.tag');

        $deletedTags = $tagService->removeTagFromMultimediaObject($resource, $request->get('tagId'));

        $json = array('deleted' => array(), 'recommended' => array());
        foreach($deletedTags as $n){
            $json['deleted'][] = array(
                                       'id' => $n->getId(),
                                       'cod' => $n->getCod(),
                                       'name' => $n->getTitle(),
                                       'group' => $n->getPath()
                                       );
        }

        return new JsonResponse($json);
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
            $tag = $this->get('pumukitschema.factory')->getTagsByCod($cod, false);
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

    /**
     * {@inheritdoc}
     */
    public function bottomAction(Request $request)
    {
        $config = $this->getConfiguration();
        $resource = $this->findOr404($request);

        //rank zero is the template
        $new_rank = 1;
        $resource->setRank($new_rank);
        $this->domainManager->update($resource);

        $this->addFlash('success', 'up');

        return $this->redirectToRoute(
            $config->getRedirectRoute('index'),
            $config->getRedirectParameters()
        );
    }
}
