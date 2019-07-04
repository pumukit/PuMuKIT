<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\NewAdminBundle\Event\BackofficeEvents;
use Pumukit\NewAdminBundle\Event\PublicationSubmitEvent;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectMetaType;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectPubType;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\EmbeddedSocial;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class MultimediaObjectController extends SortableAdminController implements NewAdminControllerInterface
{
    public static $resourceName = 'mms';
    public static $repoName = MultimediaObject::class;

    /**
     * Overwrite to search criteria with date.
     *
     * @Template
     *
     * @param Request $request
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     *
     * @return array|Response
     */
    public function indexAction(Request $request)
    {
        $session = $this->get('session');
        $factoryService = $this->get('pumukitschema.factory');

        $sessionId = $session->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->query->get('id'), $sessionId);
        if (!$series) {
            throw $this->createNotFoundException();
        }

        if ($request->get('page')) {
            $page = (int) $request->get('page', 1);
            if ($page < 1) {
                $page = 1;
            }
            $session->set('admin/mms/page', $page);
        }

        if ($request->get('paginate', null)) {
            $session->set('admin/mms/paginate', $request->get('paginate', 10));
        }

        $session->set('admin/series/id', $series->getId());

        $mms = $this->getListMultimediaObjects($series);

        $update_session = true;
        foreach ($mms as $mm) {
            if ($mm->getId() == $session->get('admin/mms/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $session->remove('admin/mms/id');
        }

        return [
            'series' => $series,
            'mms' => $mms,
            'disable_pudenew' => !$this->container->getParameter('show_latest_with_pudenew'),
        ];
    }

    /**
     * Redirect to the series list of a multimedia object series. Update
     * the session to set the correct page and selected object.
     *
     * Route: /admin/mm/{id}
     */
    public function shortenerAction(MultimediaObject $mm, Request $request)
    {
        $this->updateSession($mm);

        return $this->redirectToRoute('pumukitnewadmin_mms_index', ['id' => $mm->getSeries()->getId()]);
    }

    /**
     * Create new resource.
     */
    public function createAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $session = $this->get('session');

        $factoryService = $this->get('pumukitschema.factory');

        $sessionId = $session->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->get('id'), $sessionId);
        $session->set('admin/series/id', $series->getId());

        $mmobj = $factoryService->createMultimediaObject($series, true, $this->getUser());
        $this->get('pumukitschema.sorted_multimedia_object')->reorder($series);

        if ($request->attributes->has('microsite_custom_tag')) {
            $sTagCode = $request->attributes->get('microsite_custom_tag');

            $dm = $this->get('doctrine_mongodb')->getManager();
            $aTag = $dm->getRepository(Tag::class)->findOneBy(['cod' => $sTagCode]);

            if ($aTag) {
                $mmobj->addTag($aTag);
                $dm->flush();
            }
        }

        // After reordering the session page is updated
        $dm->refresh($mmobj);
        $this->updateSession($mmobj);

        return new JsonResponse(
            [
                'seriesId' => $series->getId(),
                'mmId' => $mmobj->getId(),
            ]
        );
    }

    /**
     * Overwrite to update the session.
     *
     * @Template
     */
    public function showAction(Request $request)
    {
        $data = $this->findOr404($request);

        $activeEditor = $this->checkHasEditor();

        return [
            'mm' => $data,
            'active_editor' => $activeEditor,
        ];
    }

    /**
     * Display the form for editing or update the resource.
     *
     * @Template
     */
    public function editAction(Request $request)
    {
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
        $formMeta = $this->createForm(MultimediaObjectMetaType::class, $resource, ['translator' => $translator, 'locale' => $locale]);
        $options = [
            'not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS),
            'translator' => $translator,
            'locale' => $locale,
        ];
        $formPub = $this->createForm(MultimediaObjectPubType::class, $resource, $options);

        //If the 'pudenew' tag is not being used, set the display to 'false'.
        if (!$this->container->getParameter('show_latest_with_pudenew')) {
            $this->get('doctrine_mongodb.odm.document_manager')
                ->getRepository(Tag::class)
                ->findOneByCod('PUDENEW')
                ->setDisplay(false)
            ;
        }
        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $jobs = $this->get('pumukitencoder.job')->getNotFinishedJobsByMultimediaObjectId($resource->getId());

        $notMasterProfiles = $this->get('pumukitencoder.profile')->getProfiles(null, true, false);

        $template = $resource->isPrototype() ? '_template' : '';

        $activeEditor = $this->checkHasEditor();
        $notChangePubChannel = !$this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL);
        $allBundles = $this->container->getParameter('kernel.bundles');
        $opencastExists = array_key_exists('PumukitOpencastBundle', $allBundles);

        $allGroups = $this->getAllGroups();

        $showSimplePubTab = $this->container->getParameter('pumukit_new_admin.show_naked_pub_tab');

        return [
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
            'show_simple_pub_tab' => $showSimplePubTab,
        ];
    }

    /**
     * @Template
     */
    public function linksAction(MultimediaObject $resource)
    {
        $mmService = $this->get('pumukitschema.multimedia_object');
        $warningOnUnpublished = $this->container->getParameter('pumukit.warning_on_unpublished');

        return [
            'mm' => $resource,
            'is_published' => $mmService->isPublished($resource, 'PUCHWEBTV'),
            'is_hidden' => $mmService->isHidden($resource, 'PUCHWEBTV'),
            'is_playable' => $mmService->hasPlayableResource($resource),
            'warning_on_unpublished' => $warningOnUnpublished,
        ];
    }

    /**
     * Display the form for editing or update the resource.
     *
     * @Template("PumukitNewAdminBundle:MultimediaObject:updatesocial.html.twig")
     */
    public function updatesocialAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $multimediaObject = $dm->getRepository(MultimediaObject::class)->findOneById(new \MongoId($request->request->get('id')));
        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $social = $multimediaObject->getEmbeddedSocial();
            if (!$social) {
                $social = new EmbeddedSocial();
            }
            $social->setTwitter($request->request->get('twitter'));
            $social->setEmail($request->request->get('email'));
            $dm->persist($social);

            $multimediaObject->setEmbeddedSocial($social);

            $dm->flush();
        }

        return ['mm' => $multimediaObject];
    }

    /**
     * Display the form for editing or update the resource.
     */
    public function updatemetaAction(Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $personService = $this->get('pumukitschema.person');

        $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();
        $allGroups = $this->getAllGroups();

        try {
            $personalScopeRole = $personService->getPersonalScopeRole();
        } catch (\Exception $e) {
            return new Response($e, Response::HTTP_BAD_REQUEST);
        }

        $roles = $personService->getRoles();
        if (null === $roles) {
            throw new \Exception('Not found any role.');
        }

        $parentTags = $factoryService->getParentTags();

        $resource = $this->findOr404($request);
        $this->get('session')->set('admin/mms/id', $resource->getId());
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $formMeta = $this->createForm(MultimediaObjectMetaType::class, $resource, ['translator' => $translator, 'locale' => $locale]);
        $options = [
            'not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS),
            'translator' => $translator,
            'locale' => $locale,
        ];
        $formPub = $this->createForm(MultimediaObjectPubType::class, $resource, $options);

        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $notChangePubChannel = !$this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL);

        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $formMeta->handleRequest($request);
            if ($formMeta->isSubmitted() && $formMeta->isValid()) {
                $this->update($resource);

                $this->dispatchUpdate($resource);
                $this->get('pumukitschema.sorted_multimedia_object')->reorder($resource->getSeries());

                $referer = $request->headers->get('referer');

                return $this->renderList($resource, $referer);
            }
        }

        return $this->render(
            'PumukitNewAdminBundle:MultimediaObject:edit.html.twig',
            [
                'mm' => $resource,
                'form_meta' => $formMeta->createView(),
                'form_pub' => $formPub->createView(),
                'roles' => $roles,
                'personal_scope_role' => $personalScopeRole,
                'personal_scope_role_code' => $personalScopeRoleCode,
                'pub_channels' => $pubChannelsTags,
                'pub_decisions' => $pubDecisionsTags,
                'parent_tags' => $parentTags,
                'not_change_pub_channel' => $notChangePubChannel,
                'groups' => $allGroups,
            ]
        );
    }

    /**
     * Display the form for editing or update the resource.
     */
    public function updatepubAction(Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $personService = $this->get('pumukitschema.person');

        $resource = $this->findOr404($request);

        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById(null, $resource->getSeries()->getId());
        if (null === $series) {
            throw new \Exception('Series with id '.$request->get('id').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());

        $parentTags = $factoryService->getParentTags();

        $this->get('session')->set('admin/mms/id', $resource->getId());

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $formMeta = $this->createForm(MultimediaObjectMetaType::class, $resource, ['translator' => $translator, 'locale' => $locale]);
        $options = [
            'not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS),
            'translator' => $translator,
            'locale' => $locale,
        ];
        $formPub = $this->createForm(MultimediaObjectPubType::class, $resource, $options);

        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $notChangePubChannel = !$this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL);

        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $formPub->handleRequest($request);

            if ($formPub->isSubmitted() && $formPub->isValid()) {
                if (!$notChangePubChannel) {
                    $resource = $this->updateTags($request->get('pub_channels', null), 'PUCH', $resource);
                }

                $event = new PublicationSubmitEvent($resource, $request);
                $this->get('event_dispatcher')->dispatch(BackofficeEvents::PUBLICATION_SUBMIT, $event);

                $resource = $this->updateTags($request->get('pub_decisions', null), 'PUDE', $resource);

                $this->update($resource);

                $this->dispatchUpdate($resource);
                $this->get('pumukitschema.sorted_multimedia_object')->reorder($series);

                $mms = $this->getListMultimediaObjects($series);
                if (false === strpos($request->server->get('HTTP_REFERER'), 'mmslist')) {
                    return $this->render(
                        'PumukitNewAdminBundle:MultimediaObject:list.html.twig',
                        [
                            'series' => $series,
                            'mms' => $mms,
                        ]
                    );
                }

                return $this->redirectToRoute('pumukitnewadmin_mms_listall', [], 301);
            }
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

        $allGroups = $this->getAllGroups();

        return $this->render(
            'PumukitNewAdminBundle:MultimediaObject:edit.html.twig',
            [
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
            ]
        );
    }

    /**
     * @Template("PumukitNewAdminBundle:MultimediaObject:listtagsajax.html.twig")
     */
    public function getChildrenTagAction(Tag $tag, Request $request)
    {
        return [
            'nodes' => $tag->getChildren(),
            'parent' => $tag->getId(),
            'mmId' => $request->get('mm_id'),
            'block_tag' => $request->get('tag_id'),
        ];
    }

    /**
     * Add Tag.
     */
    public function addTagAction(Request $request)
    {
        $resource = $this->findOr404($request);

        $tagService = $this->get('pumukitschema.tag');

        try {
            $addedTags = $tagService->addTagToMultimediaObject($resource, $request->get('tagId'));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $json = ['added' => [], 'recommended' => []];
        foreach ($addedTags as $n) {
            $json['added'][] = [
                'id' => $n->getId(),
                'cod' => $n->getCod(),
                'name' => $n->getTitle(),
                'group' => $n->getPath(),
            ];
        }

        $json['prototype'] = $resource->isPrototype();

        return new JsonResponse($json);
    }

    /**
     * Delete Tag.
     */
    public function deleteTagAction(Request $request)
    {
        $resource = $this->findOr404($request);

        $tagService = $this->get('pumukitschema.tag');

        try {
            $deletedTags = $tagService->removeTagFromMultimediaObject($resource, $request->get('tagId'));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $json = ['deleted' => [], 'recommended' => []];
        foreach ($deletedTags as $n) {
            $json['deleted'][] = [
                'id' => $n->getId(),
                'cod' => $n->getCod(),
                'name' => $n->getTitle(),
                'group' => $n->getPath(),
            ];
        }

        $json['prototype'] = $resource->isPrototype();

        return new JsonResponse($json);
    }

    /**
     * Search a tag.
     */
    public function searchTagAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository(Tag::class);

        $search_text = $request->get('search_text');
        $lang = $request->getLocale();
        $mmId = $request->get('mmId');

        $parent = $repo->findOneById($request->get('parent'));
        $parent_path = str_replace('|', '\\|', $parent->getPath());

        $qb = $dm->createQueryBuilder(Tag::class);
        $children = $qb->addOr($qb->expr()->field('title.'.$lang)->equals(new \MongoRegex('/.*'.$search_text.'.*/i')))
            ->addOr($qb->expr()->field('cod')->equals(new \MongoRegex('/.*'.$search_text.'.*/i')))
            ->addAnd($qb->expr()->field('path')->equals(new \MongoRegex('/'.$parent_path.'(.+[\|]+)+/')))
                  //->limit(20)
            ->getQuery()
            ->execute()
        ;
        $result = $children->toArray();

        if (!$result) {
            return $this->render(
                'PumukitNewAdminBundle:MultimediaObject:listtagsajaxnone.html.twig',
                ['mmId' => $mmId, 'parentId' => $parent->getId()]
            );
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
            ['nodes' => $result, 'mmId' => $mmId, 'block_tag' => $parent->getId(), 'parent' => $parent, 'search_text' => $search_text]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function bottomAction(Request $request)
    {
        $resource = $this->findOr404($request);

        //rank zero is the template
        $new_rank = 1;
        $resource->setRank($new_rank);
        $this->update($resource);

        $this->addFlash('success', 'up');
        $this->get('pumukitschema.sorted_multimedia_object')->reorder($resource->getSeries());

        return $this->redirectToIndex();
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

        return $this->redirect($this->generateUrl(
            'pumukitnewadmin_mms_list',
            ['seriesId' => $seriesId]
        ));
    }

    public function batchDeleteAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $factory = $this->get('pumukitschema.factory');
        foreach ($ids as $id) {
            $resource = $this->find($id);
            if (!$resource || !$this->isUserAllowedToDelete($resource)) {
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
        $seriesId = $resource->getSeries()->getId();

        $this->get('pumukitschema.factory')->cloneMultimediaObject($resource);

        return $this->redirect($this->generateUrl(
            'pumukitnewadmin_mms_list',
            ['seriesId' => $seriesId]
        ));
    }

    /**
     * Batch invert announce selected.
     */
    public function invertAnnounceAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $tagService = $this->get('pumukitschema.tag');
        $tagNew = $this->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(Tag::class)->findOneByCod('PUDENEW');
        foreach ($ids as $id) {
            $resource = $this->find($id);

            if (!$resource) {
                continue;
            }

            if ($resource->containsTagWithCod('PUDENEW')) {
                $tagService->removeTagFromMultimediaObject($resource, $tagNew->getId());
            } else {
                $tagService->addTagToMultimediaObject($resource, $tagNew->getId());
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

        return $this->render(
            'PumukitNewAdminBundle:MultimediaObject:list.html.twig',
            [
                'series' => $series,
                'mms' => $mms,
            ]
        );
    }

    /**
     * Cut multimedia objects.
     */
    public function cutAction(Request $request)
    {
        $ids = $request->get('ids');
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
            $multimediaObject = $dm->getRepository(MultimediaObject::class)->find($id);

            if (!$multimediaObject) {
                continue;
            }

            if ($id === $this->get('session')->get('admin/mms/id')) {
                $this->get('session')->remove('admin/mms/id');
            }
            $multimediaObject->setSeries($series);
            if (Series::SORT_MANUAL === $series->getSorting()) {
                $multimediaObject->setRank(-1);
            }

            $dm->persist($multimediaObject);
        }
        $dm->persist($series);
        $dm->flush();

        $this->get('session')->remove('admin/mms/cut');

        $this->get('pumukitschema.sorted_multimedia_object')->reorder($series);

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

        $series->setSorting($request->get('sorting', Series::SORT_MANUAL));
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $dm->persist($series);
        $dm->flush();

        $this->get('pumukitschema.sorted_multimedia_object')->reorder($series);

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list'));
    }

    /**
     * Sync tags for all multimedia objects of a series.
     */
    public function syncTagsAction(Request $request)
    {
        $all = $request->query->get('all');

        $multimediaObjectRepo = $this->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(MultimediaObject::class)
        ;
        $multimediaObject = $multimediaObjectRepo
            ->find($request->query->get('id'))
        ;

        if (!$multimediaObject) {
            return new JsonResponse('Not Found', 404);
        }

        $mms = $multimediaObjectRepo
            ->createQueryBuilder()
            ->field('_id')->notEqual($multimediaObject->getId())
            ->field('type')->notEqual(MultimediaObject::TYPE_LIVE)
            ->field('series')->references($multimediaObject->getSeries())
            ->getQuery()->execute()
            ->toArray()
        ;

        if ($all) {
            $targetTags = $multimediaObject->getTags()->toArray();

            $this->get('pumukitschema.tag')->resetCategoriesForCollections($mms, $targetTags);
        } else {
            $factoryService = $this->get('pumukitschema.factory');
            $parentTags = $factoryService->getParentTags();
            $parentTagsToSync = [];

            foreach ($parentTags as $parentTag) {
                if ($parentTag->getDisplay() && !$parentTag->getProperty('hide_in_tag_group')) {
                    $parentTagsToSync[] = $parentTag;
                }
            }

            $this->get('pumukitschema.tag')->syncTagsForCollections(
                $mms,
                $multimediaObject->getTags()->toArray(),
                $parentTagsToSync
            );
        }

        return new JsonResponse('');
    }

    /**
     * Render tags tree via AJAX.
     */
    public function reloadTagsAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository(Tag::class);

        $mmId = $request->get('mmId');
        $parent = $repo->findOneById(''.$request->get('parentId'));

        return $this->render(
            'PumukitNewAdminBundle:MultimediaObject:listtagsajax.html.twig',
            ['nodes' => $parent->getChildren(), 'mmId' => $mmId, 'block_tag' => $parent->getId(), 'parent' => 'root']
        );
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
            $addGroups = $request->get('addGroups', []);
            if ('string' === gettype($addGroups)) {
                $addGroups = json_decode($addGroups, true);
            }
            $deleteGroups = $request->get('deleteGroups', []);
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
                    $response = [
                        'redirect' => 1,
                        'url' => $this->generateUrl('pumukitnewadmin_series_index', ['id' => $seriesId]),
                    ];

                    return new JsonResponse($response, JsonResponse::HTTP_OK);
                }
            } catch (\Exception $e) {
                $response = [
                    'redirect' => 1,
                    'url' => $this->generateUrl('pumukitnewadmin_series_index', ['id' => $seriesId]),
                ];

                return new JsonResponse($response, JsonResponse::HTTP_OK);
            }
        }

        return new JsonResponse(['success', 'redirect' => 0]);
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
            $groups = ['addGroups' => [], 'deleteGroups' => []];
        }
        $info = [
            'type' => $type,
            'password' => $password,
            'groups' => $groups,
        ];

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
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $specialTranslationService = $this->get('pumukitschema.special_translation');
        if ($multimediaObject->isLive()) {
            $broadcasts = $embeddedBroadcastService->getAllTypes(true);
        } else {
            $broadcasts = $embeddedBroadcastService->getAllTypes();
        }
        $allGroups = $this->getAllGroups();
        $template = $multimediaObject->isPrototype() ? '_template' : '';
        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            try {
                $type = $request->get('type', null);
                $password = $request->get('password', null);
                $addGroups = $request->get('addGroups', []);
                if ('string' === gettype($addGroups)) {
                    $addGroups = json_decode($addGroups, true);
                }
                $deleteGroups = $request->get('deleteGroups', []);
                if ('string' === gettype($deleteGroups)) {
                    $deleteGroups = json_decode($deleteGroups, true);
                }
                $this->modifyBroadcastGroups($multimediaObject, $type, $password, $addGroups, $deleteGroups);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
            }
            $embeddedBroadcast = $multimediaObject->getEmbeddedBroadcast();
            $jsonResponse = [
                'description' => (string) $embeddedBroadcast,
                'descriptioni18n' => $specialTranslationService->getI18nEmbeddedBroadcast($embeddedBroadcast),
                'template' => $template,
            ];

            return new JsonResponse($jsonResponse);
        }

        return [
            'mm' => $multimediaObject,
            'broadcasts' => $broadcasts,
            'groups' => $allGroups,
            'template' => $template,
        ];
    }

    /**
     * User last relation.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function userLastRelationAction(Request $request)
    {
        $loggedInUser = $this->getUser();
        $userService = $this->get('pumukitschema.user');
        $response = null;
        if (!$userService->hasPersonalScope($loggedInUser)) {
            return new JsonResponse(false, Response::HTTP_OK);
        }
        if ($request->isMethod('POST')) {
            try {
                $mmId = $request->get('mmId', null);
                $personId = $request->get('personId', null);
                $owners = $request->get('owners', []);
                $addGroups = $request->get('addGroups', []);
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
        return ['multimediaObject' => $multimediaObject];
    }

    /**
     * List the external player properties of a multimedia object in a modal.
     *
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     * @Template
     */
    public function listExternalPlayerAction(MultimediaObject $multimediaObject, Request $request)
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('pumukitnewadmin_mms_listexternalproperties', ['id' => $multimediaObject->getId()]))
            ->add('url', UrlType::class, ['required' => false, 'attr' => ['class' => 'form-control']])
            ->add('save', SubmitType::class, ['label' => 'OK', 'attr' => ['class' => 'btn btn-block btn-pumukit btn-raised']])
            ->getForm()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $dm = $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $data['url'] = urldecode($data['url']);
            $multimediaObject->setProperty('externalplayer', $data['url']);
            $dm->flush();

            return $this->forward('PumukitNewAdminBundle:Track:list', ['multimediaObject' => $multimediaObject]);
        }

        return ['multimediaObject' => $multimediaObject, 'form' => $form->createView()];
    }

    /**
     * List the external player properties of a multimedia object in a modal.
     *
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     */
    public function deleteExternalPropertyAction(MultimediaObject $multimediaObject)
    {
        $dm = $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $multimediaObject->setProperty('externalplayer', '');
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * Show a player of a multimedia object in a modal.
     *
     * @Template
     */
    public function modalPreviewAction(Multimediaobject $multimediaObject)
    {
        $mmService = $this->get('pumukitschema.multimedia_object');

        return [
            'multimediaObject' => $multimediaObject,
            'is_playable' => $mmService->hasPlayableResource($multimediaObject),
        ];
    }

    /**
     * Used to update table_mms_status_wrapper via AJAX.
     *
     * @Template
     */
    public function statusAction(MultimediaObject $mm, Request $request)
    {
        return ['mm' => $mm];
    }

    /**
     * Overwrite to search criteria with date.
     *
     * @Template
     */
    public function indexAllAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $criteria = $this->getCriteria($request->get('criteria', []));
        $resources = $this->getResources($request, $criteria);

        $update_session = true;
        foreach ($resources as $mm) {
            if ($mm->getId() == $this->get('session')->get('admin/mmslist/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $this->get('session')->remove('admin/mmslist/id');
        }

        $aRoles = $dm->getRepository(Role::class)->findAll();
        $aPubChannel = $dm->getRepository(Tag::class)->findOneBy(['cod' => 'PUBCHANNELS']);
        $aChannels = $dm->getRepository(Tag::class)->findBy(['parent.$id' => new \MongoId($aPubChannel->getId())]);

        $multimediaObjectLabel = $this->get('translator')->trans($this->container->getParameter('pumukit_new_admin.multimedia_object_label'));
        $statusPub = [
            MultimediaObject::STATUS_PUBLISHED => 'Published',
            MultimediaObject::STATUS_BLOCKED => 'Blocked',
            MultimediaObject::STATUS_HIDDEN => 'Hidden',
        ];

        return [
            'mms' => $resources,
            'roles' => $aRoles,
            'statusPub' => $statusPub,
            'pubChannels' => $aChannels,
            'disable_pudenew' => !$this->container->getParameter('show_latest_with_pudenew'),
            'multimedia_object_label' => $multimediaObjectLabel,
        ];
    }

    /**
     * List action
     * Overwrite to pass series parameter.
     */
    public function listAllAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $resources = $this->getResources($request, $criteria);

        return $this->render(
            'PumukitNewAdminBundle:MultimediaObject:listAll.html.twig',
            [
                'mms' => $resources,
                'disable_pudenew' => !$this->container->getParameter('show_latest_with_pudenew'),
            ]
        );
    }

    /**
     * Gets the criteria values.
     *
     * @param mixed $criteria
     */
    public function getCriteria($criteria)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $criteria = $request->get('criteria', []);

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/'.$this->getResourceName().'/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/'.$this->getResourceName().'/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/'.$this->getResourceName().'/criteria', []);

        return $this->get('pumukitnewadmin.multimedia_object_search')->processMMOCriteria($criteria, $request->getLocale());
    }

    /**
     * Gets the list of resources according to a criteria.
     *
     * @param Request $request
     * @param $criteria
     *
     * @return Pagerfanta
     */
    public function getResources(Request $request, $criteria)
    {
        $sorting = $this->getSorting($request, $this->getResourceName());
        $session = $this->get('session');
        $session_namespace = 'admin/'.$this->getResourceName();

        $resources = $this->createPager($criteria, $sorting);

        if ($request->get('page', null)) {
            $page = (int) $request->get('page', 1);
            if ($page < 1) {
                $page = 1;
            }

            $session->set($session_namespace.'/page', $page);
        }

        if ($request->get('paginate', null)) {
            $session->set($session_namespace.'/paginate', $request->get('paginate', 10));
        }

        $resources
            ->setMaxPerPage($session->get($session_namespace.'/paginate', 10))
            ->setNormalizeOutOfRangePages(true)
            ->setCurrentPage($session->get($session_namespace.'/page', 1))
        ;

        return $resources;
    }

    public function getSorting(Request $request = null, $session_namespace = null)
    {
        $session = $this->get('session');

        if ($sorting = $request->get('sorting')) {
            $session->set('admin/'.$session_namespace.'/type', current($sorting));
            $session->set('admin/'.$session_namespace.'/sort', key($sorting));
        }

        $value = $session->get('admin/'.$session_namespace.'/type', 'desc');
        $key = $session->get('admin/'.$session_namespace.'/sort', 'public_date');

        if ('title' == $key) {
            $key .= '.'.$request->getLocale();
        }

        return  [$key => $value];
    }

    public function getResourceName()
    {
        $request = $this->container->get('request_stack')->getMasterRequest();
        $sRoute = $request->get('_route');

        return (false === strpos($sRoute, 'all')) ? 'mms' : 'mmslist';
    }

    public function updatePropertyAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $multimediaObject = $dm->getRepository(MultimediaObject::class)->findOneById(new \MongoId($request->get('id')));
        $method = $request->getMethod();
        if (in_array($method, ['POST'])) {
            $multimediaObject->setProperty('paellalayout', $request->get('paellalayout'));
            $dm->flush();
        }

        return new JsonResponse(['paellalayout' => $multimediaObject->getProperty('paellalayout')]);
    }

    /**
     * Sync selected metadata on all mmobjs of the series.
     *
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     *
     * @throws \Exception
     *
     * @return array
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "id"})
     *
     * @Template("PumukitNewAdminBundle:MultimediaObject:modalsyncmetadata.html.twig")
     */
    public function modalSyncMedatadaAction(Request $request, MultimediaObject $multimediaObject)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $syncService = $this->container->get('pumukitnewadmin.multimedia_object_sync');

        $tags = $dm->getRepository(Tag::class)->findBy(
            ['metatag' => true, 'display' => true, 'properties.hide_in_tag_group' => ['$exists' => false]],
            ['cod' => 1]
        );
        if (!$tags) {
            throw new \Exception($translator->trans('No tags defined with metatag'));
        }
        $roles = $dm->getRepository(Role::class)->findBy([], ["name.{$locale}" => 1]);
        if (0 === count($roles)) {
            throw new \Exception($translator->trans('No roles defined'));
        }

        return [
            'fields' => $syncService->getSyncFields(),
            'multimediaObject' => $multimediaObject,
            'tags' => $tags,
            'roles' => $roles,
        ];
    }

    /**
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     *
     * @return JsonResponse
     */
    public function updateMultimediaObjectSyncAction(Request $request, MultimediaObject $multimediaObject)
    {
        $translator = $this->get('translator');
        $message = $translator->trans('Sync metadata was fail.');

        $syncService = $this->container->get('pumukitnewadmin.multimedia_object_sync');
        $multimediaObjects = $syncService->getMultimediaObjectsToSync($multimediaObject);

        $syncFieldsSelected = $request->request->all();
        if (empty($syncFieldsSelected)) {
            $message = $translator->trans('No fields selected to sync');
        }

        if ($multimediaObjects) {
            $syncService->syncMetadata($multimediaObjects, $multimediaObject, $syncFieldsSelected);
            $message = $translator->trans('Sync metadata was done successfully');
        }

        return new JsonResponse($message, JsonResponse::HTTP_OK);
    }

    /**
     * Update Tags in Multimedia Object from form.
     *
     * @param mixed $checkedTags
     * @param mixed $codStart
     * @param mixed $resource
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
     * Get the view list of multimedia objects
     * belonging to a series.
     *
     * @param null|mixed $newMultimediaObjectId
     */
    protected function getListMultimediaObjects(Series $series, $newMultimediaObjectId = null)
    {
        $session = $this->get('session');
        $page = $session->get('admin/mms/page', 1);

        $maxPerPage = $session->get('admin/mms/paginate', 10);

        $sorting = ['rank' => 'asc'];

        $mmsQueryBuilder = $this
            ->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(MultimediaObject::class)
            ->getQueryBuilderOrderedBy($series, $sorting)
        ;

        $adapter = new DoctrineODMMongoDBAdapter($mmsQueryBuilder);
        $mms = new Pagerfanta($adapter);

        $mms
            ->setMaxPerPage($maxPerPage)
            ->setNormalizeOutOfRangePages(true)
        ;

        $mms->setCurrentPage($page);

        return $mms;
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

        return array_key_exists('pumukit_videoeditor_index', $routes);
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

    private function getAllParents($element, $tags, $top_parent)
    {
        if (null !== $element->getParent()) {
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
     * Modify MultimediaObject Groups.
     *
     * @param mixed $addGroups
     * @param mixed $deleteGroups
     */
    private function modifyMultimediaObjectGroups(MultimediaObject $multimediaObject, $addGroups = [], $deleteGroups = [])
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $groupRepo = $dm->getRepository(Group::class);
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
     *
     * @param mixed $type
     * @param mixed $password
     * @param mixed $addGroups
     * @param mixed $deleteGroups
     */
    private function modifyBroadcastGroups(MultimediaObject $multimediaObject, $type = EmbeddedBroadcast::TYPE_PUBLIC, $password = '', $addGroups = [], $deleteGroups = [])
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $groupRepo = $dm->getRepository(Group::class);
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $embeddedBroadcastService->updateTypeAndName($type, $multimediaObject, false);
        if (EmbeddedBroadcast::TYPE_PASSWORD === $type) {
            $embeddedBroadcastService->updatePassword($password, $multimediaObject, false);
        } elseif (EmbeddedBroadcast::TYPE_GROUPS === $type) {
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

        if (EmbeddedBroadcast::TYPE_GROUPS !== $type) {
            $embeddedBroadcast = $multimediaObject->getEmbeddedBroadcast();
            if ($embeddedBroadcast->getGroups()) {
                foreach ($embeddedBroadcast->getGroups() as $group) {
                    $embeddedBroadcast->removeGroup($group);
                }
            }
        }

        $dm->flush();
    }

    private function getResourceGroups($groups = [])
    {
        $groupService = $this->get('pumukitschema.group');
        $addGroups = [];
        $deleteGroups = [];
        $addGroupsIds = [];
        $allGroupsIds = [];

        foreach ($groups as $group) {
            $addGroups[$group->getId()] = [
                'key' => $group->getKey(),
                'name' => $group->getName(),
                'origin' => $group->getOrigin(),
            ];
            $addGroupsIds[] = new \MongoId($group->getId());
        }
        $allGroups = $this->getAllGroups();
        foreach ($allGroups as $group) {
            $allGroupsIds[] = new \MongoId($group->getId());
        }
        $groupsToDelete = $groupService->findByIdNotInOf($addGroupsIds, $allGroupsIds);
        foreach ($groupsToDelete as $group) {
            $deleteGroups[$group->getId()] = [
                'key' => $group->getKey(),
                'name' => $group->getName(),
                'origin' => $group->getOrigin(),
            ];
        }

        return ['addGroups' => $addGroups, 'deleteGroups' => $deleteGroups];
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
        $personRepo = $dm->getRepository(Person::class);
        foreach ($multimediaObject->getProperty('owners') as $owner) {
            $person = $personRepo->find($owner);
            if ($person) {
                if ($loggedInUser === $person->getUser()) {
                    $userInOwners = true;

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

    private function renderList(MultimediaObject $resource, $referer)
    {
        if (false === strpos($referer, 'mmslist')) {
            $mms = $this->getListMultimediaObjects($resource->getSeries());

            return $this->render(
                'PumukitNewAdminBundle:MultimediaObject:list.html.twig',
                [
                    'mms' => $mms,
                    'series' => $resource->getSeries(),
                ]
            );
        }

        return $this->redirectToRoute('pumukitnewadmin_mms_listall', [], 301);
    }

    private function updateSession(MultimediaObject $mm)
    {
        $session = $this->get('session');
        $paginate = $session->get('admin/mms/paginate', 10);

        $page = (int) ceil($mm->getRank() / $paginate);
        if ($page < 1) {
            $page = 1;
        }

        $session->set('admin/mms/page', $page);
        $session->set('admin/mms/id', $mm->getId());
    }
}
