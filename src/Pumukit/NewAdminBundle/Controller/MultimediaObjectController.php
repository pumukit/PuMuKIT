<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectMetaType;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectPubType;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class MultimediaObjectController extends SortableAdminController implements NewAdminController
{
    /**
     * Overwrite to search criteria with date
     * @Template
     */
    public function indexAction(Request $request)
    {
        $config = $this->getConfiguration();
        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);

        $factoryService = $this->get('pumukitschema.factory');

        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->query->get('id'), $sessionId);
        if(!$series) throw $this->createNotFoundException();

        $this->get('session')->set('admin/series/id', $series->getId());

        if($request->query->has('mmid')) {
            $this->get('session')->set('admin/mms/id', $request->query->get('mmid'));
        }

        $mms = $this->getListMultimediaObjects($series);

        $update_session = true;
        foreach($mms as $mm) {
            if($mm->getId() == $this->get('session')->get('admin/mms/id')){
                $update_session = false;
            }
        }

        if($update_session){
            $this->get('session')->remove('admin/mms/id');
        }

        return array(
                     'series' => $series,
                     'mms' => $mms
                     );
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

       $mmobj = $factoryService->createMultimediaObject($series, true, $this->getUser());

       $this->get('session')->set('admin/mms/id', $mmobj->getId());

       return new JsonResponse(array(
				     'seriesId' => $series->getId(),
				     'mmId' => $mmobj->getId()
				     ));
    }

    /**
     * Overwrite to update the session.
     * @Template
     */
    public function showAction(Request $request)
    {
      $config = $this->getConfiguration();
      $data = $this->findOr404($request);

      $this->get('session')->set('admin/mms/id', $data->getId());

      $roles = $this->get('pumukitschema.person')->getRoles();

      $activeEditor = $this->checkHasEditor();

      return array(
                   'mm' => $data,
                   'roles' => $roles,
                   'active_editor' => $activeEditor,
                   );
    }

    /**
     * Display the form for editing or update the resource.
     * @Template
     */
    public function editAction(Request $request)
    {
        $config = $this->getConfiguration();

        $factoryService = $this->get('pumukitschema.factory');
        $personService = $this->get('pumukitschema.person');

        $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();

        try {
            $personalScopeRole = $personService->getPersonalScopeRole();
        } catch (\Exception $e) {
            return new Response($e, Response::HTTP_BAD_REQUEST);
        }

        $roles = $personService->getRoles();
        if (null === $roles){
            throw new \Exception('Not found any role.');
        }

        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->get('seriesId'), $sessionId);

        if (null === $series){
            throw new \Exception('Series with id '.$request->get('seriesId').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());

        $parentTags = $factoryService->getParentTags();

        $resource = $this->findOr404($request);
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $formMeta = $this->createForm(new MultimediaObjectMetaType($translator, $locale), $resource);
        $options = array('not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS));
        $formPub = $this->createForm(new MultimediaObjectPubType($translator, $locale), $resource, $options);

        //If the 'pudenew' tag is not being used, set the display to 'false'.
        if(!$this->container->getParameter('show_latest_with_pudenew')) {
            $this->get('doctrine_mongodb.odm.document_manager')
                 ->getRepository('PumukitSchemaBundle:Tag')
                 ->findOneByCod('PUDENEW')
                 ->setDisplay(false);
        }
        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $jobs = $this->get('pumukitencoder.job')->getNotFinishedJobsByMultimediaObjectId($resource->getId());

        $notMasterProfiles = $this->get('pumukitencoder.profile')->getProfiles(null, true, false);

        $template = $resource->isPrototype() ? '_template' : '';

        $isPublished = null;
        $playableResource = null;

        $activeEditor = $this->checkHasEditor();
        $notChangePubChannel = !$this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL);
        $allBundles = $this->container->getParameter('kernel.bundles');
        $opencastExists = array_key_exists('PumukitOpencastBundle', $allBundles);

        $groupService = $this->get('pumukitschema.group');
        $allGroups = $groupService->findAll();

        return array(
                     'mm'                       => $resource,
                     'form_meta'                => $formMeta->createView(),
                     'form_pub'                 => $formPub->createView(),
                     'series'                   => $series,
                     'roles'                    => $roles,
                     'personal_scope_role'      => $personalScopeRole,
                     'personal_scope_role_code' => $personalScopeRoleCode,
                     'pub_channels'             => $pubChannelsTags,
                     'pub_decisions'            => $pubDecisionsTags,
                     'parent_tags'              => $parentTags,
                     'jobs'                     => $jobs,
                     'not_master_profiles'      => $notMasterProfiles,
                     'template'                 => $template,
                     'active_editor'            => $activeEditor,
                     'opencast_exists'          => $opencastExists,
                     'not_change_pub_channel'   => $notChangePubChannel,
                     'groups'                   => $allGroups
                     );
    }


    /**
     *
     * @Template
     */
    public function linksAction(MultimediaObject $resource)
    {
        $mmService = $this->get('pumukitschema.multimedia_object');
        $warningOnUnpublished = $this->container->getParameter('pumukit2.warning_on_unpublished');

        return array(
             'mm' => $resource,
             'is_published' => $mmService->isPublished($resource, 'PUCHWEBTV'),
             'is_hidden' => $mmService->isHidden($resource, 'PUCHWEBTV'),
             'is_playable' => $mmService->hasPlayableResource($resource),
             'warning_on_unpublished' => $warningOnUnpublished
        );
    }


    /**
     * Display the form for editing or update the resource.
     */
    public function updatemetaAction(Request $request)
    {
        $config = $this->getConfiguration();

        $factoryService = $this->get('pumukitschema.factory');
        $personService = $this->get('pumukitschema.person');
        $groupService = $this->get('pumukitschema.group');

        $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();
        $allGroups = $groupService->findAll();

        try {
            $personalScopeRole = $personService->getPersonalScopeRole();
        } catch (\Exception $e) {
            return new Response($e, Response::HTTP_BAD_REQUEST);
        }

        $roles = $personService->getRoles();
        if (null === $roles){
            throw new \Exception('Not found any role.');
        }

        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById(null, $sessionId);
        if (null === $series){
            throw new \Exception('Series with id '.$request->get('id').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());

        $parentTags = $factoryService->getParentTags();

        $resource = $this->findOr404($request);
        $this->get('session')->set('admin/mms/id', $resource->getId());
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $formMeta = $this->createForm(new MultimediaObjectMetaType($translator, $locale), $resource);
        $options = array('not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS));
        $formPub = $this->createForm(new MultimediaObjectPubType($translator, $locale), $resource, $options);

        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $notChangePubChannel = !$this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL);

        $method = $request->getMethod();
        if (in_array($method, array('POST', 'PUT', 'PATCH')) &&
            $formMeta->submit($request, !$request->isMethod('PATCH'))->isValid()) {
          $this->domainManager->update($resource);

          $this->dispatchUpdate($resource);

          if ($config->isApiRequest()) {
            return $this->handleView($this->view($formMeta));
          }

          /*
            $criteria = $this->getCriteria($config);
            $resources = $this->getResources($request, $config, $criteria);
          */

          $mms = $this->getListMultimediaObjects($series);

          return $this->render('PumukitNewAdminBundle:MultimediaObject:list.html.twig',
                               array(
                                     'series' => $series,
                                     'mms' => $mms
                                     )
                               );
        }

        if ($config->isApiRequest()) {
            return $this->handleView($this->view($formMeta));
        }

        return $this->render('PumukitNewAdminBundle:MultimediaObject:edit.html.twig',
                             array(
                                   'mm'                       => $resource,
                                   'form_meta'                => $formMeta->createView(),
                                   'form_pub'                 => $formPub->createView(),
                                   'series'                   => $series,
                                   'roles'                    => $roles,
                                   'personal_scope_role'      => $personalScopeRole,
                                   'personal_scope_role_code' => $personalScopeRoleCode,
                                   'pub_channels'             => $pubChannelsTags,
                                   'pub_decisions'            => $pubDecisionsTags,
                                   'parent_tags'              => $parentTags,
                                   'not_change_pub_channel'   => $notChangePubChannel,
                                   'groups'                   => $allGroups
                                   )
                             );
    }

    /**
     * Display the form for editing or update the resource.
     */
    public function updatepubAction(Request $request)
    {
        $config = $this->getConfiguration();

        $factoryService = $this->get('pumukitschema.factory');
        $personService = $this->get('pumukitschema.person');

        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById(null, $sessionId);
        if (null === $series){
            throw new \Exception('Series with id '.$request->get('id').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());

        $parentTags = $factoryService->getParentTags();

        $resource = $this->findOr404($request);
        $this->get('session')->set('admin/mms/id', $resource->getId());

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $formMeta = $this->createForm(new MultimediaObjectMetaType($translator, $locale), $resource);
        $options = array('not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS));
        $formPub = $this->createForm(new MultimediaObjectPubType($translator, $locale), $resource, $options);

        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $notChangePubChannel = !$this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL);

        $method = $request->getMethod();
        if (in_array($method, array('POST', 'PUT', 'PATCH')) &&
            $formPub->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            if (!$notChangePubChannel) {
                $resource = $this->updateTags($request->get('pub_channels', null), "PUCH", $resource);
            }
            $resource = $this->updateTags($request->get('pub_decisions', null), "PUDE", $resource);

            $this->domainManager->update($resource);

            $this->dispatchUpdate($resource);

            if ($config->isApiRequest()) {
                return $this->handleView($this->view($formPub));
            }

            $mms = $this->getListMultimediaObjects($series);

            return $this->render('PumukitNewAdminBundle:MultimediaObject:list.html.twig',
                                 array(
                                       'series' => $series,
                                       'mms' => $mms
                                       )
                                 );
        }

        if ($config->isApiRequest()) {
            return $this->handleView($this->view($formPub));
        }

        $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();

        try {
            $personalScopeRole = $personService->getPersonalScopeRole();
        } catch (\Exception $e) {
            return new Response($e, Response::HTTP_BAD_REQUEST);
        }

        $roles = $personService->getRoles();
        if (null === $roles){
            throw new \Exception('Not found any role.');
        }
        $groupService = $this->get('pumukitschema.group');
        $allGroups = $groupService->findAll();

        return $this->render('PumukitNewAdminBundle:MultimediaObject:edit.html.twig',
                             array(
                                   'mm'                       => $resource,
                                   'form_meta'                => $formMeta->createView(),
                                   'form_pub'                 => $formPub->createView(),
                                   'series'                   => $series,
                                   'roles'                    => $roles,
                                   'personal_scope_role'      => $personalScopeRole,
                                   'personal_scope_role_code' => $personalScopeRoleCode,
                                   'pub_channels'             => $pubChannelsTags,
                                   'pub_decisions'            => $pubDecisionsTags,
                                   'parent_tags'              => $parentTags,
                                   'not_change_pub_channel'   => $notChangePubChannel,
                                   'groups'                   => $allGroups
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

        try{
            $addedTags = $tagService->addTagToMultimediaObject($resource, $request->get('tagId'));
        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }

        $json = array('added' => array(), 'recommended' => array());
        foreach($addedTags as $n){
            $json['added'][] = array(
                                     'id' => $n->getId(),
                                     'cod' => $n->getCod(),
                                     'name' => $n->getTitle(),
                                     'group' => $n->getPath()
                                     );
        }

        $json['prototype'] = $resource->isPrototype();
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

        try{
            $deletedTags = $tagService->removeTagFromMultimediaObject($resource, $request->get('tagId'));
        }catch (\Exception $e){
            throw new \Exception($e->getMessage());
        }

        $json = array('deleted' => array(), 'recommended' => array());
        foreach($deletedTags as $n){
            $json['deleted'][] = array(
                                       'id' => $n->getId(),
                                       'cod' => $n->getCod(),
                                       'name' => $n->getTitle(),
                                       'group' => $n->getPath()
                                       );
        }

        $json['prototype'] = $resource->isPrototype();
        return new JsonResponse($json);
    }

    /**
     * Update Tags in Multimedia Object from form
     */
    protected function updateTags($checkedTags, $codStart, $resource)
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
    protected function getListMultimediaObjects(Series $series, $newMultimediaObjectId=null)
    {
        $session = $this->get('session');
        $page = $session->get('admin/mms/page', 1);
        $maxPerPage = $session->get('admin/mms/paginate', 10);

        $sorting = array("rank" => "asc");
        $mmsQueryBuilder = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:MultimediaObject')
          ->getQueryBuilderOrderedBy($series, $sorting);

        $adapter = new DoctrineODMMongoDBAdapter($mmsQueryBuilder);
        $mms = new Pagerfanta($adapter);

        $mms
          ->setMaxPerPage($maxPerPage)
          ->setNormalizeOutOfRangePages(true);

        /*
          NOTE: Multimedia Objects are sorted by ascending rank.
          A new MultimediaObject is created with last rank,
          so it will be at the end of the list.
          We update the page if a new page is created to show the
          the new MultimediaObject in new last page.
        */
        if ($newMultimediaObjectId && (($mms->getNbResults()/$maxPerPage) > $page)) {
            $page = $mms->getNbPages();
            $session->set('admin/mms/page', $page);
        }
        $mms->setCurrentPage($page);

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

    /**
     * Delete action
     * Overwrite to pass series parameter
     */
    public function deleteAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $resourceId = $resource->getId();
        $seriesId = $resource->getSeries()->getId();

        if(!$this->isUserAllowedToDelete($resource))
            return new Response('You don\'t have enough permissions to delete this mmobj. Contact your administrator.', Response::HTTP_FORBIDDEN);

        try {
            $this->get('pumukitschema.factory')->deleteMultimediaObject($resource);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        if ($resourceId === $this->get('session')->get('admin/mms/id')){
            $this->get('session')->remove('admin/mms/id');
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list',
                                                  array('seriesId' => $seriesId)));
    }

    public function batchDeleteAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)){
            $ids = json_decode($ids, true);
        }

        $factory = $this->get('pumukitschema.factory');
        foreach ($ids as $id) {
            $resource = $this->find($id);
            if(!$this->isUserAllowedToDelete($resource))
                continue;
            try{
                $factory->deleteMultimediaObject($resource);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
            if ($id === $this->get('session')->get('admin/mms/id')){
                $this->get('session')->remove('admin/mms/id');
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list'));
    }

    /**
     * Generate Magic Url action
     */
    public function generateMagicUrlAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $mmobjService = $this->get('pumukitschema.multimedia_object');
        $response = $mmobjService->resetMagicUrl($resource);
        return new Response($response);
    }


    /**
     * Clone action
     */
    public function cloneAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $resourceId = $resource->getId();
        $seriesId = $resource->getSeries()->getId();

        $this->get('pumukitschema.factory')->cloneMultimediaObject($resource);

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list',
                                                  array('seriesId' => $seriesId)));
    }

    /**
     * Batch invert announce selected
     */
    public function invertAnnounceAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)){
            $ids = json_decode($ids, true);
        }

        $tagService = $this->get('pumukitschema.tag');
        $tagNew = $this->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:Tag')->findOneByCod('PUDENEW');
        foreach ($ids as $id){
            $resource = $this->find($id);
            if ($resource->containsTagWithCod('PUDENEW')){
                $addedTags = $tagService->removeTagFromMultimediaObject($resource, $tagNew->getId());
            }else{
                $addedTags = $tagService->addTagToMultimediaObject($resource, $tagNew->getId());
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list'));
    }

    /**
     * List action
     * Overwrite to pass series parameter
     */
    public function listAction(Request $request)
    {
        $config = $this->getConfiguration();
        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);
        $factoryService = $this->get('pumukitschema.factory');
        $seriesId = $request->get('seriesId', null);
        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($seriesId, $sessionId);

        $mms = $this->getListMultimediaObjects($series, $request->get('newMmId', null));

        $update_session = true;
        foreach($mms as $mm) {
            if($mm->getId() == $this->get('session')->get('admin/mms/id')){
                $update_session = false;
            }
        }

        if($update_session){
            $this->get('session')->remove('admin/mms/id');
        }

        return $this->render('PumukitNewAdminBundle:MultimediaObject:list.html.twig',
                             array(
                                   'series' => $series,
                                   'mms' => $mms
                                   )
                             );
    }

    /**
     * Cut multimedia objects
     */
    public function cutAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');
        if ('string' === gettype($ids)){
            $ids = json_decode($ids, true);
        }
        $this->get('session')->set('admin/mms/cut', $ids);

        return new JsonResponse($ids);
    }

    /**
     * Paste multimedia objects
     */
    public function pasteAction(Request $request)
    {
        if (!($this->get('session')->has('admin/mms/cut'))){
            throw new \Exception('Not found any multimedia object to paste.');
        }

        $ids = $this->get('session')->get('admin/mms/cut');

        $factoryService = $this->get('pumukitschema.factory');
        $seriesId = $request->get('seriesId', null);
        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($seriesId, $sessionId);

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        foreach($ids as $id){
            $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')
              ->find($id);
            $mmSeriesId = $multimediaObject->getSeries()->getId();
            if ($id === $this->get('session')->get('admin/mms/id')){
                $this->get('session')->remove('admin/mms/id');
            }
            $multimediaObject->setSeries($series);
            $dm->persist($multimediaObject);
        }
        $dm->persist($series);
        $dm->flush();

        $this->get('session')->remove('admin/mms/cut');

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list'));
    }


    /**
     * Reorder multimedia objects
     */
    public function reorderAction(Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->get('id'), $sessionId);

        $sorting = array($request->get("fieldName", "rank") => $request->get("order", 1));

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mms = $dm
          ->getRepository('PumukitSchemaBundle:MultimediaObject')
          ->findOrderedBy($series, $sorting);

        $rank = 1;
        foreach($mms as $mm){
            $dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
              ->update()
              ->field('rank')->set($rank++)
              ->field('_id')->equals($mm->getId())
              ->getQuery()
              ->execute();
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list'));
    }

    /**
     * Sync tags for all multimedia objects of a series
     */
    public function syncTagsAction(Request $request)
    {
        $multimediaObjectRepo = $this->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $multimediaObject = $multimediaObjectRepo
          ->find($request->query->get('id'));

        if(!$multimediaObject)
          return new JsonResponse("Not Found", 404);

        $mms = $multimediaObjectRepo->findBySeries($multimediaObject->getSeries())->toArray();
        $tags = $multimediaObject->getTags()->toArray();
        $this->get('pumukitschema.tag')->resetTags($mms, $tags);

        return new JsonResponse("");
    }

    protected function dispatchUpdate($multimediaObject)
    {
        $event = new MultimediaObjectEvent($multimediaObject);
        $this->get('event_dispatcher')->dispatch(SchemaEvents::MULTIMEDIAOBJECT_UPDATE, $event);
    }

    //Workaround function to check if the VideoEditorBundle is installed.
    protected function checkHasEditor()
    {
        $router = $this->get('router');
        $routes = $router->getRouteCollection()->all();
        $activeEditor = array_key_exists('pumukit_videoeditor_index', $routes);

        return $activeEditor;
    }

    /**
     * Returns true if the user has enough permissions to delete the $resource passed
     *
     * This function will always return true if the user the MODIFY_ONWER permission. Otherwise,
     * it checks if it is the owner of the object (and there are no other owners) and returns false if not.
     */
    protected function isUserAllowedToDelete(MultimediaObject $resource)
    {
        if(!$this->isGranted(Permission::MODIFY_OWNER)) {
            $loggedInUser = $this->getUser();
            $personService = $this->get('pumukitschema.person');
            $person = $personService->getPersonFromLoggedInUser($loggedInUser);
            $role = $personService->getPersonalScopeRole();
            if( !$person ||
                !$resource->containsPersonWithRole($person, $role) ||
                count($resource->getPeopleByRole($role, true)) > 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * Update groups action
     */
    public function updateGroupsAction(Request $request)
    {
        // TODO. Add security permission to access.
        $multimediaObject = $this->findOr404($request);
        if ('POST' === $request->getMethod()){
            $addGroups = $request->get('addGroups', array());
            if ('string' === gettype($addGroups)){
                $addGroups = json_decode($addGroups, true);
            }
            $deleteGroups = $request->get('deleteGroups', array());
            if ('string' === gettype($deleteGroups)){
                $deleteGroups = json_decode($deleteGroups, true);
            }
            try {
                $this->modifyMultimediaObjectGroups($multimediaObject, $addGroups, $deleteGroups);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }

        return new JsonResponse(array('success'));
    }

    /**
     * Get groups
     */
    public function getGroupsAction(Request $request)
    {
        $multimediaObject = $this->findOr404($request);
        $groups = $this->getResourceGroups($multimediaObject->getGroups());

        return new JsonResponse($groups);
    }

    /**
     * Get embedded Broadcast groups
     */
    public function getBroadcastInfoAction(Request $request)
    {
        $multimediaObject = $this->findOr404($request);
        $embeddedBroadcast = $multimediaObject->getEmbeddedBroadcast();
        if ($embeddedBroadcast) {
            $type = $embeddedBroadcast->getType();
            $password = $embeddedBroadcast->getPassword();
            $groups = $this->getResourceGroups($embeddedBroadcast->getGroups());
        } else {
            $type = EmbeddedBroadcast::TYPE_PUBLIC;
            $password = '';
            $groups = array('addGroups' => array(), 'deleteGroups' => array());
        }
        $info = array(
                      'type' => $type,
                      'password' => $password,
                      'groups' => $groups
                      );

        return new JsonResponse($info);
    }

    /**
     * Update Broadcast Action
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "id"})
     * @Template("PumukitNewAdminBundle:MultimediaObject:updatebroadcast.html.twig")
     */
    public function updateBroadcastAction(MultimediaObject $multimediaObject, Request $request)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $broadcasts = $this->get('pumukitschema.embeddedbroadcast')->getAllTypes();
        $groupService = $this->get('pumukitschema.group');
        $allGroups = $groupService->findAll();
        $template = $multimediaObject->isPrototype() ? '_template' : '';
        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            try {
                $type = $request->get('type', null);
                $password = $request->get('password', null);
                $addGroups = $request->get('addGroups', array());
                if ('string' === gettype($addGroups)){
                    $addGroups = json_decode($addGroups, true);
                }
                $deleteGroups = $request->get('deleteGroups', array());
                if ('string' === gettype($deleteGroups)){
                    $deleteGroups = json_decode($deleteGroups, true);
                }
                $this->modifyBroadcastGroups($multimediaObject, $type, $password, $addGroups, $deleteGroups);
            } catch (\Exception $e) {
                return new JsonResponse(array('error' => $e->getMessage()), JsonResponse::HTTP_BAD_REQUEST);
            }
            $embeddedBroadcast = $multimediaObject->getEmbeddedBroadcast();
            $jsonResponse = array(
                                  'description' => (string)$embeddedBroadcast,
                                  'template' => $template
                                  );

            return new JsonResponse($jsonResponse);
        }
        return array(
                     'mm' => $multimediaObject,
                     'broadcasts' => $broadcasts,
                     'groups' => $allGroups,
                     'template' => $template
                     );

    }

    /**
     * User last relation
     */
    public function userLastRelationAction(Request $request)
    {
        $loggedInUser = $this->getUser();
        $userService = $this->get('pumukitschema.user');
        if (!$userService->hasPersonalScope($loggedInUser)) {
            return new JsonResponse(false, Response::HTTP_OK);
        }
        if ($request->isMethod('GET')) {
            try {
                $personId = $request->get('personId', null);
                $owners = $request->get('owners', array());
                if ('string' === gettype($owners)){
                    $addGroups = json_decode($owners, true);
                }
                $addGroups = $request->get('addGroups', array());
                if ('string' === gettype($addGroups)){
                    $addGroups = json_decode($addGroups, true);
                }
                $response = $this->isUserLastRelation($loggedInUser, $personId, $owners, $addGroups);
            } catch (\Exception $e) {
                return new JsonResponse(array('error' => $e->getMessage()), JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }

    /**
     * Is User last relation
     */
    private function isUserLastRelation(User $loggedInUser, $personId = null, $owners = array(), $addGroups = array())
    {
        $personToRemoveIsLogged = false;
        $userInOwners = false;
        $userInAddGroups = false;

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $personRepo = $dm->getRepository('PumukitSchemaBundle:Person');

        $personToRemove = $personRepo->find($personId);
        if ($personToRemove) {
            $userService = $this->get('pumukitschema.user');
            if (!$userService->hasPersonalScope($personToRemove->getUser())) {
                return false;
            }
            if ($loggedInUser === $personToRemove->getUser()) {
                $personToRemoveIsLogged = true;
            }
        }

        foreach ($owners as $owner) {
            $personId = end(explode('_', $owner));
            $person = $personRepo->find($personId);
            if ($person) {
                if ($loggedInUser === $person->getUser()) {
                    $userInOwners = true;
                    break;
                }
            }
        }

        $userGroups = $loggedInUser->getGroups()->toArray();
        foreach ($addGroups as $addGroup){
            $groupId = end(explode('_', $addGroup));
            $group = $groupRepo->find($groupId);
            if ($group) {
                if (in_array($group, $userGroups)) {
                    $userInAddGroups = true;
                    break;
                }
            }
        }

        // Show warning??
        if (($personToRemoveIsLogged && !$userInAddGroups) || (!$userInOwners && !$userInAddGroups)) {
            return true;
        }

        return false;
    }

    /**
     * Modify MultimediaObject Groups
     */
    private function modifyMultimediaObjectGroups(MultimediaObject $multimediaObject, $addGroups = array(), $deleteGroups = array())
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $groupRepo = $dm->getRepository('PumukitSchemaBundle:Group');
        $multimediaObjectService = $this->get('pumukitschema.multimedia_object');
        $index = $multimediaObject->isPrototype() ? 4 : 3;
        foreach ($addGroups as $addGroup){
            $groupId = explode('_', $addGroup)[$index];
            $group = $groupRepo->find($groupId);
            if ($group) {
                $multimediaObjectService->addGroup($group, $multimediaObject, false);
            }
        }
        foreach ($deleteGroups as $deleteGroup){
            $groupId = explode('_', $deleteGroup)[$index];
            $group = $groupRepo->find($groupId);
            if ($group) {
                $multimediaObjectService->deleteGroup($group, $multimediaObject, false);
            }
        }

        $dm->flush();
    }

    /**
     * Modify EmbeddedBroadcast Groups
     */
    private function modifyBroadcastGroups(MultimediaObject $multimediaObject, $type = EmbeddedBroadcast::TYPE_PUBLIC, $password = '', $addGroups = array(), $deleteGroups = array())
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $groupRepo = $dm->getRepository('PumukitSchemaBundle:Group');
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $embeddedBroadcastService->updateTypeAndName($type, $multimediaObject, false);
        if ($type === EmbeddedBroadcast::TYPE_PASSWORD) {
            $embeddedBroadcastService->updatePassword($password, $multimediaObject, false);
        } elseif ($type === EmbeddedBroadcast::TYPE_GROUPS) {
            $index = 3;
            foreach ($addGroups as $addGroup){
                $groupId = explode('_', $addGroup)[$index];
                $group = $groupRepo->find($groupId);
                if ($group) {
                    $embeddedBroadcastService->addGroup($group, $multimediaObject, false);
                }
            }
            foreach ($deleteGroups as $deleteGroup){
                $groupId = explode('_', $deleteGroup)[$index];
                $group = $groupRepo->find($groupId);
                if ($group) {
                    $embeddedBroadcastService->deleteGroup($group, $multimediaObject, false);
                }
            }
        }

        $dm->flush();
    }

    private function getResourceGroups($groups = array())
    {
        $groupService = $this->get('pumukitschema.group');
        $addGroups = array();
        $deleteGroups = array();
        $addGroupsIds = array();
        foreach ($groups as $group) {
            $addGroups[$group->getId()] = array(
                                                'key' => $group->getKey(),
                                                'name' => $group->getName(),
                                                'origin' => $group->getOrigin()
                                                );
            $addGroupsIds[] = new \MongoId($group->getId());
        }
        $groupsToDelete = $groupService->findByIdNotIn($addGroupsIds);
        foreach ($groupsToDelete as $group) {
            $deleteGroups[$group->getId()] = array(
                                                   'key' => $group->getKey(),
                                                   'name' => $group->getName(),
                                                   'origin' => $group->getOrigin()
                                                   );
        }

        return array('addGroups' => $addGroups, 'deleteGroups' => $deleteGroups);
    }
}
