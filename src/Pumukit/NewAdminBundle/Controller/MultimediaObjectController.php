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
     * Overwrite to search criteria with date.
     *
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
        if (!$series) {
            throw $this->createNotFoundException();
        }

        $this->get('session')->set('admin/series/id', $series->getId());

        $mms = $this->getListMultimediaObjects($series);

        $update_session = true;
        foreach ($mms as $mm) {
            if ($mm->getId() == $this->get('session')->get('admin/mms/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $this->get('session')->remove('admin/mms/id');
        }

        return array(
            'series' => $series,
            'mms' => $mms,
            'disable_pudenew' => !$this->container->getParameter('show_latest_with_pudenew'),
        );
    }

    /**
     * Redirect to the series list of a multimedia object series. Update
     * the session to set the correct page and selected object.
     *
     * Route: /admin/mm/{id}
     */
    public function shortenerAction(MultimediaObject $mm, Request $request)
    {
        $session = $this->get('session');
        $paginate = $session->get('admin/mms/paginate', 10);

        $page = (int) ceil($mm->getRank() / $paginate);
        if ($page < 1) {
            $page = 1;
        }

        $session->set('admin/mms/id', $mm->getId());
        $session->set('admin/mms/page', $page);

        return $this->redirectToRoute('pumukitnewadmin_mms_index', array('id' => $mm->getSeries()->getId()));
    }

    /**
     * Create new resource.
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
                     'mmId' => $mmobj->getId(),
                     ));
    }

    /**
     * Overwrite to update the session.
     *
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
     *
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
        if (null === $roles) {
            throw new \Exception('Not found any role.');
        }

        /*$sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->get('seriesId'), $sessionId);

        if (null === $series) {
            throw new \Exception('Series with id '.$request->get('seriesId').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());*/

        $parentTags = $factoryService->getParentTags();

        $resource = $this->findOr404($request);
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $formMeta = $this->createForm(new MultimediaObjectMetaType($translator, $locale), $resource);
        $options = array('not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS));
        $formPub = $this->createForm(new MultimediaObjectPubType($translator, $locale), $resource, $options);

        //If the 'pudenew' tag is not being used, set the display to 'false'.
        if (!$this->container->getParameter('show_latest_with_pudenew')) {
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
                     'mm' => $resource,
                     'form_meta' => $formMeta->createView(),
                     'form_pub' => $formPub->createView(),
                     //'series' => $series,
                     'roles' => $roles,
                     'personal_scope_role' => $personalScopeRole,
                     'personal_scope_role_code' => $personalScopeRoleCode,
                     'pub_channels' => $pubChannelsTags,
                     'pub_decisions' => $pubDecisionsTags,
                     'parent_tags' => $parentTags,
                     'jobs' => $jobs,
                     'not_master_profiles' => $notMasterProfiles,
                     'template' => $template,
                     'active_editor' => $activeEditor,
                     'opencast_exists' => $opencastExists,
                     'not_change_pub_channel' => $notChangePubChannel,
                     'groups' => $allGroups,
                     );
    }

    /**
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
             'warning_on_unpublished' => $warningOnUnpublished,
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
        if (null === $roles) {
            throw new \Exception('Not found any role.');
        }

        /*$sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById(null, $sessionId);
        if (null === $series) {
            throw new \Exception('Series with id '.$request->get('id').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());*/

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
            $referer = $request->headers->get('referer');

            return $this->renderList($resource, $referer);
        }

        if ($config->isApiRequest()) {
            return $this->handleView($this->view($formMeta));
        }

        return $this->render('PumukitNewAdminBundle:MultimediaObject:edit.html.twig',
                             array(
                                   'mm' => $resource,
                                   'form_meta' => $formMeta->createView(),
                                   'form_pub' => $formPub->createView(),
                                   //'series' => $series,
                                   'roles' => $roles,
                                   'personal_scope_role' => $personalScopeRole,
                                   'personal_scope_role_code' => $personalScopeRoleCode,
                                   'pub_channels' => $pubChannelsTags,
                                   'pub_decisions' => $pubDecisionsTags,
                                   'parent_tags' => $parentTags,
                                   'not_change_pub_channel' => $notChangePubChannel,
                                   'groups' => $allGroups,
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
        if (null === $series) {
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
                $resource = $this->updateTags($request->get('pub_channels', null), 'PUCH', $resource);
            }
            $resource = $this->updateTags($request->get('pub_decisions', null), 'PUDE', $resource);

            $this->domainManager->update($resource);

            $this->dispatchUpdate($resource);

            if ($config->isApiRequest()) {
                return $this->handleView($this->view($formPub));
            }

            $mms = $this->getListMultimediaObjects($series);

            return $this->render('PumukitNewAdminBundle:MultimediaObject:list.html.twig',
                                 array(
                                       'series' => $series,
                                       'mms' => $mms,
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
        if (null === $roles) {
            throw new \Exception('Not found any role.');
        }
        $groupService = $this->get('pumukitschema.group');
        $allGroups = $groupService->findAll();

        return $this->render('PumukitNewAdminBundle:MultimediaObject:edit.html.twig',
                             array(
                                   'mm' => $resource,
                                   'form_meta' => $formMeta->createView(),
                                   'form_pub' => $formPub->createView(),
                                   'series' => $series,
                                   'roles' => $roles,
                                   'personal_scope_role' => $personalScopeRole,
                                   'personal_scope_role_code' => $personalScopeRoleCode,
                                   'pub_channels' => $pubChannelsTags,
                                   'pub_decisions' => $pubDecisionsTags,
                                   'parent_tags' => $parentTags,
                                   'not_change_pub_channel' => $notChangePubChannel,
                                   'groups' => $allGroups,
                                   )
                             );
    }

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
     * Add Tag.
     */
    public function addTagAction(Request $request)
    {
        $config = $this->getConfiguration();

        $resource = $this->findOr404($request);

        $tagService = $this->get('pumukitschema.tag');

        try {
            $addedTags = $tagService->addTagToMultimediaObject($resource, $request->get('tagId'));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $json = array('added' => array(), 'recommended' => array());
        foreach ($addedTags as $n) {
            $json['added'][] = array(
                                     'id' => $n->getId(),
                                     'cod' => $n->getCod(),
                                     'name' => $n->getTitle(),
                                     'group' => $n->getPath(),
                                     );
        }

        $json['prototype'] = $resource->isPrototype();

        return new JsonResponse($json);
    }

    /**
     * Delete Tag.
     */
    public function deleteTagAction(Request $request)
    {
        $config = $this->getConfiguration();

        $resource = $this->findOr404($request);

        $tagService = $this->get('pumukitschema.tag');

        try {
            $deletedTags = $tagService->removeTagFromMultimediaObject($resource, $request->get('tagId'));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $json = array('deleted' => array(), 'recommended' => array());
        foreach ($deletedTags as $n) {
            $json['deleted'][] = array(
                                       'id' => $n->getId(),
                                       'cod' => $n->getCod(),
                                       'name' => $n->getTitle(),
                                       'group' => $n->getPath(),
                                       );
        }

        $json['prototype'] = $resource->isPrototype();

        return new JsonResponse($json);
    }

    /**
     * Update Tags in Multimedia Object from form.
     */
    protected function updateTags($checkedTags, $codStart, $resource)
    {
        if (null !== $checkedTags) {
            foreach ($resource->getTags() as $tag) {
                if ((0 == strpos($tag->getCod(), $codStart)) && (false !== strpos($tag->getCod(), $codStart)) &&
                    (!in_array($tag->getCod(), $checkedTags)) &&
                    (!$this->isGranted(Permission::getRoleTagDisableForPubChannel($tag->getCod())))) {
                    $resource->removeTag($tag);
                }
            }
            foreach ($checkedTags as $cod => $checked) {
                if (!$this->isGranted(Permission::getRoleTagDisableForPubChannel($cod))) {
                    $tag = $this->get('pumukitschema.factory')->getTagsByCod($cod, false);
                    $resource->addTag($tag);
                }
            }
        } else {
            foreach ($resource->getTags() as $tag) {
                if ((0 == strpos($tag->getCod(), $codStart)) &&
                    (false !== strpos($tag->getCod(), $codStart)) &&
                    (!$this->isGranted(Permission::getRoleTagDisableForPubChannel($tag->getCod())))) {
                    $resource->removeTag($tag);
                }
            }
        }

        return $resource;
    }

    /**
     * Search a tag.
     */
    public function searchTagAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $search_text = $request->get('search_text');
        $lang = $request->getLocale();
        $mmId = $request->get('mmId');

        $parent = $repo->findOneById($request->get('parent'));
        $parent_path = str_replace('|', "\|", $parent->getPath());

        $qb = $dm->createQueryBuilder('PumukitSchemaBundle:Tag');
        $children = $qb->addOr($qb->expr()->field('title.'.$lang)->equals(new \MongoRegex('/.*'.$search_text.'.*/i')))
                  ->addOr($qb->expr()->field('cod')->equals(new \MongoRegex('/.*'.$search_text.'.*/i')))
                  ->addAnd($qb->expr()->field('path')->equals(new \MongoRegex('/'.$parent_path.'(.+[\|]+)+/')))
                  //->limit(20)
                  ->getQuery()
                  ->execute();
        $result = $children->toArray();

        if (!$result) {
            return $this->render('PumukitNewAdminBundle:MultimediaObject:listtagsajaxnone.html.twig',
                                 array('mmId' => $mmId, 'parentId' => $parent->getId()));
        }

        foreach ($children->toArray() as $tag) {
            $result = $this->getAllParents($tag, $result, $parent->getId());
        }

        usort(
            $result,
            function ($x, $y) {
                return strcasecmp($x->getCod(), $y->getCod());
            }
        );

        return $this->render(
            'PumukitNewAdminBundle:MultimediaObject:listtagsajax.html.twig',
            array('nodes' => $result, 'mmId' => $mmId, 'block_tag' => $parent->getId(), 'parent' => $parent, 'search_text' => $search_text)
        );
    }

    private function getAllParents($element, $tags, $top_parent)
    {
        if ($element->getParent() != null) {
            $parentMissing = true;
            foreach ($tags as $tag) {
                if ($element->getParent() == $tag) {
                    $parentMissing = false;
                    break;
                }
            }

            if ($parentMissing) {
                $parent = $element->getParent(); //"retrieveByPKWithI18n");
                if ($parent->getId() != $top_parent) {
                    $tags[] = $parent;
                    $tags = $this->getAllParents($parent, $tags, $top_parent);
                }
            }
        }

        return $tags;
    }

    /**
     * Get the view list of multimedia objects
     * belonging to a series.
     */
    protected function getListMultimediaObjects(Series $series, $newMultimediaObjectId = null)
    {
        $session = $this->get('session');
        $page = $session->get('admin/mms/page', 1);
        $maxPerPage = $session->get('admin/mms/paginate', 10);

        $sorting = array('rank' => 'asc');
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
        if ($newMultimediaObjectId && (($mms->getNbResults() / $maxPerPage) > $page)) {
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
     * Overwrite to pass series parameter.
     */
    public function deleteAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $resourceId = $resource->getId();
        $seriesId = $resource->getSeries()->getId();

        if (!$this->isUserAllowedToDelete($resource)) {
            return new Response('You don\'t have enough permissions to delete this mmobj. Contact your administrator.', Response::HTTP_FORBIDDEN);
        }

        try {
            $this->get('pumukitschema.factory')->deleteMultimediaObject($resource);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        if ($resourceId === $this->get('session')->get('admin/mms/id')) {
            $this->get('session')->remove('admin/mms/id');
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list',
                                                  array('seriesId' => $seriesId)));
    }

    public function batchDeleteAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $factory = $this->get('pumukitschema.factory');
        foreach ($ids as $id) {
            $resource = $this->find($id);
            if (!$this->isUserAllowedToDelete($resource)) {
                continue;
            }
            try {
                $factory->deleteMultimediaObject($resource);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
            if ($id === $this->get('session')->get('admin/mms/id')) {
                $this->get('session')->remove('admin/mms/id');
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list'));
    }

    /**
     * Generate Magic Url action.
     */
    public function generateMagicUrlAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $mmobjService = $this->get('pumukitschema.multimedia_object');
        $response = $mmobjService->resetMagicUrl($resource);

        return new Response($response);
    }

    /**
     * Clone action.
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
     * Batch invert announce selected.
     */
    public function invertAnnounceAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $tagService = $this->get('pumukitschema.tag');
        $tagNew = $this->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:Tag')->findOneByCod('PUDENEW');
        foreach ($ids as $id) {
            $resource = $this->find($id);
            if ($resource->containsTagWithCod('PUDENEW')) {
                $addedTags = $tagService->removeTagFromMultimediaObject($resource, $tagNew->getId());
            } else {
                $addedTags = $tagService->addTagToMultimediaObject($resource, $tagNew->getId());
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list'));
    }

    /**
     * List action
     * Overwrite to pass series parameter.
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
        foreach ($mms as $mm) {
            if ($mm->getId() == $this->get('session')->get('admin/mms/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $this->get('session')->remove('admin/mms/id');
        }

        return $this->render('PumukitNewAdminBundle:MultimediaObject:list.html.twig',
                             array(
                                   'series' => $series,
                                   'mms' => $mms,
                                   )
                             );
    }

    /**
     * Cut multimedia objects.
     */
    public function cutAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');
        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }
        $this->get('session')->set('admin/mms/cut', $ids);

        return new JsonResponse($ids);
    }

    /**
     * Paste multimedia objects.
     */
    public function pasteAction(Request $request)
    {
        if (!($this->get('session')->has('admin/mms/cut'))) {
            throw new \Exception('Not found any multimedia object to paste.');
        }

        $ids = $this->get('session')->get('admin/mms/cut');

        $factoryService = $this->get('pumukitschema.factory');
        $seriesId = $request->get('seriesId', null);
        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($seriesId, $sessionId);

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        foreach ($ids as $id) {
            $multimediaObject = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')
              ->find($id);
            $mmSeriesId = $multimediaObject->getSeries()->getId();
            if ($id === $this->get('session')->get('admin/mms/id')) {
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
     * Reorder multimedia objects.
     */
    public function reorderAction(Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->get('id'), $sessionId);

        $sorting = array($request->get('fieldName', 'rank') => $request->get('order', 1));

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mms = $dm
          ->getRepository('PumukitSchemaBundle:MultimediaObject')
          ->findOrderedBy($series, $sorting);

        $rank = 1;
        foreach ($mms as $mm) {
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
     * Sync tags for all multimedia objects of a series.
     */
    public function syncTagsAction(Request $request)
    {
        $multimediaObjectRepo = $this->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $multimediaObject = $multimediaObjectRepo
          ->find($request->query->get('id'));

        if (!$multimediaObject) {
            return new JsonResponse('Not Found', 404);
        }

        $mms = $multimediaObjectRepo->findBySeries($multimediaObject->getSeries())->toArray();
        $tags = $multimediaObject->getTags()->toArray();
        $this->get('pumukitschema.tag')->resetCategories($mms, $tags);

        return new JsonResponse('');
    }

    /**
     * Render tags tree via AJAX.
     **/
    public function reloadTagsAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $mmId = $request->get('mmId');
        $parent = $repo->findOneById(''.$request->get('parentId'));

        return $this->render(
            'PumukitNewAdminBundle:MultimediaObject:listtagsajax.html.twig',
            array('nodes' => $parent->getChildren(), 'mmId' => $mmId, 'block_tag' => $parent->getId(), 'parent' => 'root')
        );
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
     * Returns true if the user has enough permissions to delete the $resource passed.
     *
     * This function will always return true if the user the MODIFY_ONWER permission. Otherwise,
     * it checks if it is the owner of the object (and there are no other owners) and returns false if not.
     */
    protected function isUserAllowedToDelete(MultimediaObject $resource)
    {
        if (!$this->isGranted(Permission::MODIFY_OWNER)) {
            $loggedInUser = $this->getUser();
            $personService = $this->get('pumukitschema.person');
            $person = $personService->getPersonFromLoggedInUser($loggedInUser);
            $role = $personService->getPersonalScopeRole();
            if (!$person ||
                !$resource->containsPersonWithRole($person, $role) ||
                count($resource->getPeopleByRole($role, true)) > 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update groups action.
     *
     * @Security("is_granted('ROLE_MODIFY_OWNER')")
     */
    public function updateGroupsAction(Request $request)
    {
        $multimediaObject = $this->findOr404($request);
        $series = $multimediaObject->getSeries();
        $seriesId = $series->getId();
        if ('POST' === $request->getMethod()) {
            $addGroups = $request->get('addGroups', array());
            if ('string' === gettype($addGroups)) {
                $addGroups = json_decode($addGroups, true);
            }
            $deleteGroups = $request->get('deleteGroups', array());
            if ('string' === gettype($deleteGroups)) {
                $deleteGroups = json_decode($deleteGroups, true);
            }
            try {
                $this->modifyMultimediaObjectGroups($multimediaObject, $addGroups, $deleteGroups);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
            try {
                $isUserStillRelated = $this->isUserStillRelated($multimediaObject);
                if (!$isUserStillRelated) {
                    $response = array(
                                      'redirect' => 1,
                                      'url' => $this->generateUrl('pumukitnewadmin_series_index', array('id' => $seriesId)),
                                      );

                    return new JsonResponse($response, JsonResponse::HTTP_OK);
                }
            } catch (\Exception $e) {
                $response = array(
                                  'redirect' => 1,
                                  'url' => $this->generateUrl('pumukitnewadmin_series_index', array('id' => $seriesId)),
                                  );

                return new JsonResponse($response, JsonResponse::HTTP_OK);
            }
        }

        return new JsonResponse(array('success', 'redirect' => 0));
    }

    /**
     * Get groups.
     */
    public function getGroupsAction(Request $request)
    {
        $multimediaObject = $this->findOr404($request);
        $groups = $this->getResourceGroups($multimediaObject->getGroups());

        return new JsonResponse($groups);
    }

    /**
     * Get embedded Broadcast groups.
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
                      'groups' => $groups,
                      );

        return new JsonResponse($info);
    }

    /**
     * Update Broadcast Action.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "id"})
     * @Template("PumukitNewAdminBundle:MultimediaObject:updatebroadcast.html.twig")
     */
    public function updateBroadcastAction(MultimediaObject $multimediaObject, Request $request)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $broadcasts = $embeddedBroadcastService->getAllTypes();
        $groupService = $this->get('pumukitschema.group');
        $allGroups = $groupService->findAll();
        $template = $multimediaObject->isPrototype() ? '_template' : '';
        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            try {
                $type = $request->get('type', null);
                $password = $request->get('password', null);
                $addGroups = $request->get('addGroups', array());
                if ('string' === gettype($addGroups)) {
                    $addGroups = json_decode($addGroups, true);
                }
                $deleteGroups = $request->get('deleteGroups', array());
                if ('string' === gettype($deleteGroups)) {
                    $deleteGroups = json_decode($deleteGroups, true);
                }
                $this->modifyBroadcastGroups($multimediaObject, $type, $password, $addGroups, $deleteGroups);
            } catch (\Exception $e) {
                return new JsonResponse(array('error' => $e->getMessage()), JsonResponse::HTTP_BAD_REQUEST);
            }
            $embeddedBroadcast = $multimediaObject->getEmbeddedBroadcast();
            $jsonResponse = array(
                                  'description' => (string) $embeddedBroadcast,
                                  'descriptioni18n' => $embeddedBroadcastService->getI18nDescription($embeddedBroadcast),
                                  'template' => $template,
                                  );

            return new JsonResponse($jsonResponse);
        }

        return array(
                     'mm' => $multimediaObject,
                     'broadcasts' => $broadcasts,
                     'groups' => $allGroups,
                     'template' => $template,
                     );
    }

    /**
     * User last relation.
     */
    public function userLastRelationAction(Request $request)
    {
        $loggedInUser = $this->getUser();
        $userService = $this->get('pumukitschema.user');
        if (!$userService->hasPersonalScope($loggedInUser)) {
            return new JsonResponse(false, Response::HTTP_OK);
        }
        if ($request->isMethod('POST')) {
            try {
                $mmId = $request->get('mmId', null);
                $personId = $request->get('personId', null);
                $owners = $request->get('owners', array());
                if ('string' === gettype($owners)) {
                    $addGroups = json_decode($owners, true);
                }
                $addGroups = $request->get('addGroups', array());
                if ('string' === gettype($addGroups)) {
                    $addGroups = json_decode($addGroups, true);
                }
                $response = $userService->isUserLastRelation($loggedInUser, $mmId, $personId, $owners, $addGroups);
            } catch (\Exception $e) {
                return new JsonResponse($e->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }

    /**
     * List the properties of a multimedia object in a modal.
     *
     * @Template
     */
    public function listPropertiesAction(MultimediaObject $multimediaObject)
    {
        return array('multimediaObject' => $multimediaObject);
    }

    /**
     * Show a player of a multimedia object in a modal.
     *
     * @Template
     */
    public function modalPreviewAction(Multimediaobject $multimediaObject)
    {
        $mmService = $this->get('pumukitschema.multimedia_object');

        return array(
             'multimediaObject' => $multimediaObject,
             'is_playable' => $mmService->hasPlayableResource($multimediaObject),
        );
    }

    /**
     * Used to update table_mms_status_wrapper via AJAX.
     *
     * @Template
     */
    public function statusAction(MultimediaObject $mm, Request $request)
    {
        return array('mm' => $mm);
    }

    /**
     * Modify MultimediaObject Groups.
     */
    private function modifyMultimediaObjectGroups(MultimediaObject $multimediaObject, $addGroups = array(), $deleteGroups = array())
    {
        $mmId = $multimediaObject->getId();
        $owners = $multimediaObject->getProperty('owners');
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $groupRepo = $dm->getRepository('PumukitSchemaBundle:Group');
        $multimediaObjectService = $this->get('pumukitschema.multimedia_object');
        foreach ($addGroups as $addGroup) {
            $groupIdArray = explode('_', $addGroup);
            $groupId = end($groupIdArray);
            $group = $groupRepo->find($groupId);
            if ($group) {
                $multimediaObjectService->addGroup($group, $multimediaObject, false);
            }
        }
        foreach ($deleteGroups as $deleteGroup) {
            $groupIdArray = explode('_', $deleteGroup);
            $groupId = end($groupIdArray);
            $group = $groupRepo->find($groupId);
            if ($group) {
                $multimediaObjectService->deleteGroup($group, $multimediaObject, false);
            }
        }

        $dm->flush();
    }

    /**
     * Modify EmbeddedBroadcast Groups.
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
            foreach ($addGroups as $addGroup) {
                $groupIdArray = explode('_', $addGroup);
                $groupId = end($groupIdArray);
                $group = $groupRepo->find($groupId);
                if ($group) {
                    $embeddedBroadcastService->addGroup($group, $multimediaObject, false);
                }
            }
            foreach ($deleteGroups as $deleteGroup) {
                $groupIdArray = explode('_', $deleteGroup);
                $groupId = end($groupIdArray);
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
                                                'origin' => $group->getOrigin(),
                                                );
            $addGroupsIds[] = new \MongoId($group->getId());
        }
        $groupsToDelete = $groupService->findByIdNotIn($addGroupsIds);
        foreach ($groupsToDelete as $group) {
            $deleteGroups[$group->getId()] = array(
                                                   'key' => $group->getKey(),
                                                   'name' => $group->getName(),
                                                   'origin' => $group->getOrigin(),
                                                   );
        }

        return array('addGroups' => $addGroups, 'deleteGroups' => $deleteGroups);
    }

    private function isUserStillRelated(MultimediaObject $multimediaObject)
    {
        $loggedInUser = $this->getUser();
        $superAdmin = $loggedInUser->hasRole('ROLE_SUPER_ADMIN');
        if ($superAdmin) {
            return true;
        }

        $userService = $this->get('pumukitschema.user');
        $globalScope = $userService->hasGlobalScope($loggedInUser);
        if ($globalScope) {
            return true;
        }

        $userInOwners = false;
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $personRepo = $dm->getRepository('PumukitSchemaBundle:Person');
        foreach ($multimediaObject->getProperty('owners') as $owner) {
            $person = $personRepo->find($owner);
            if ($person) {
                if ($loggedInUser === $person->getUser()) {
                    $userInOnwers = true;
                    break;
                }
            }
        }
        if ($userInOwners) {
            return true;
        }

        $userInGroups = false;
        $userGroups = $loggedInUser->getGroups()->toArray();
        foreach ($multimediaObject->getGroups() as $mmGroup) {
            if (in_array($mmGroup, $userGroups)) {
                $userInGroups = true;
                break;
            }
        }
        if ($userInGroups) {
            return true;
        }

        return false;
    }

    /**
     * Overwrite to search criteria with date.
     *
     * @Template
     */
    public function indexAllAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $config = $this->getConfiguration();
        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);

        $update_session = true;
        foreach ($resources as $mm) {
            if ($mm->getId() == $this->get('session')->get('admin/mmslist/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $this->get('session')->remove('admin/mmslist/id');
        }

        $aRoles = $dm->getRepository('PumukitSchemaBundle:Role')->findAll();
        $aPubChannel = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => 'PUBCHANNELS'));
        $aChannels = $dm->getRepository('PumukitSchemaBundle:Tag')->findBy(array('parent.$id' => new \MongoId($aPubChannel->getId())));

        return array(
            'mms' => $resources,
            'roles' => $aRoles,
            'pubChannels' => $aChannels,
            'disable_pudenew' => !$this->container->getParameter('show_latest_with_pudenew'),
        );
    }

    /**
     * List action
     * Overwrite to pass series parameter.
     */
    public function listAllAction(Request $request)
    {
        $config = $this->getConfiguration();
        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);

        return $this->render('PumukitNewAdminBundle:MultimediaObject:listAll.html.twig',
            array(
                'mms' => $resources,
                'disable_pudenew' => !$this->container->getParameter('show_latest_with_pudenew'),
            )
        );
    }

    private function renderList(MultimediaObject $resource, $referer)
    {
        if (strpos($referer, 'mmslist') === false) {
            $mms = $this->getListMultimediaObjects($resource->getSeries());

            return $this->render(
                'PumukitNewAdminBundle:MultimediaObject:list.html.twig',
                array(
                    'mms' => $mms,
                    'series' => $resource->getSeries(),
                )
            );
        } else {
            $request = $this->getRequest();
            $mms = $this->getListAllMultimediaObjects($request);

            return $this->render('PumukitNewAdminBundle:MultimediaObject:listAll.html.twig', array('mms' => $mms));
        }
    }

    /**
     * Gets the criteria values.
     */
    public function getCriteria($config)
    {
        $criteria = $this->getRequest()->get('criteria', array());

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/mmslist/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/mmslist/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/mmslist/criteria', array());

        $new_criteria = $this->get('pumukitnewadmin.series_search')->processMMOCriteria($criteria, true);

        return $new_criteria;
    }

    /**
     * Gets the list of resources according to a criteria.
     */
    public function getResources(Request $request, $config, $criteria)
    {
        $sorting = $config->getSorting();
        $repository = $this->getRepository();
        $session = $this->get('session');
        $session_namespace = 'admin/mmlist';

        if ($config->isPaginated()) {
            $resources = $this
                ->resourceResolver
                ->getResource($repository, 'createPaginator', array($criteria, $sorting));

            if ($request->get('page', null)) {
                $session->set($session_namespace.'/page', $request->get('page', 1));
            }

            if ($request->get('paginate', null)) {
                $session->set($session_namespace.'/paginate', $request->get('paginate', 10));
            }

            $resources
                ->setMaxPerPage($session->get($session_namespace.'/paginate', 10))
                ->setNormalizeOutOfRangePages(true)
                ->setCurrentPage($session->get($session_namespace.'/page', 1));
        } else {
            $resources = $this
                ->resourceResolver
                ->getResource($repository, 'findBy', array($criteria, $sorting, $config->getLimit()));
        }

        return $resources;
    }
}
