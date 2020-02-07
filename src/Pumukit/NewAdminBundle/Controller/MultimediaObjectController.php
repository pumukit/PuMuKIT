<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pagerfanta\Pagerfanta;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\NewAdminBundle\Event\BackofficeEvents;
use Pumukit\NewAdminBundle\Event\PublicationSubmitEvent;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectMetaType;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectPubType;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectTemplateMetaType;
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
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\UserService;
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
class MultimediaObjectController extends SortableAdminController
{
    public static $resourceName = 'mms';
    public static $repoName = MultimediaObject::class;

    public function __construct(DocumentManager $documentManager, PaginationService $paginationService, FactoryService $factoryService, GroupService $groupService, UserService $userService)
    {
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService);
    }

    /**
     * Overwrite to search criteria with date.
     *
     * @Template("PumukitNewAdminBundle:MultimediaObject:index.html.twig")
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     *
     * @return array|Response
     */
    public function indexAction(Request $request)
    {
        $session = $this->get('session');

        $sessionId = $session->get('admin/series/id', null);
        $series = $this->factoryService->findSeriesById($request->query->get('id'), $sessionId);
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
            'disable_pudenew' => !$this->getParameter('show_latest_with_pudenew'),
        ];
    }

    /**
     * Redirect to the series list of a multimedia object series. Update
     * the session to set the correct page and selected object.
     *
     * Route: /admin/mm/{id}
     *
     * @param mixed $id
     */
    public function shortenerAction($id, Request $request)
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['id' => $id]);
        if (!$multimediaObject) {
            $template = 'PumukitNewAdminBundle:MultimediaObject:404notfound.html.twig';
            $options = ['id' => $id];

            return new Response($this->renderView($template, $options), 404);
        }
        $this->updateSession($multimediaObject);

        return $this->redirectToRoute('pumukitnewadmin_mms_index', ['id' => $multimediaObject->getSeries()->getId()]);
    }

    /**
     * Create new resource.
     */
    public function createAction(Request $request)
    {
        $session = $this->get('session');

        $sessionId = $session->get('admin/series/id', null);
        $series = $this->factoryService->findSeriesById($request->get('id'), $sessionId);
        $session->set('admin/series/id', $series->getId());

        $mmobj = $this->factoryService->createMultimediaObject($series, true, $this->getUser());
        $this->sortedMultimediaObjectService->reorder($series);

        if ($request->attributes->has('microsite_custom_tag')) {
            $sTagCode = $request->attributes->get('microsite_custom_tag');

            $aTag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $sTagCode]);

            if ($aTag) {
                $mmobj->addTag($aTag);
                $this->documentManager->flush();
            }
        }

        // After reordering the session page is updated
        $this->documentManager->refresh($mmobj);
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
     * @Template("PumukitNewAdminBundle:MultimediaObject:show.html.twig")
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
     * @Template("PumukitNewAdminBundle:MultimediaObject:edit.html.twig")
     */
    public function editAction(Request $request)
    {
        $personalScopeRoleCode = $this->personService->getPersonalScopeRoleCode();

        try {
            $personalScopeRole = $this->personService->getPersonalScopeRole();
        } catch (\Exception $e) {
            return new Response($e, Response::HTTP_BAD_REQUEST);
        }

        $roles = $this->personService->getRoles();
        if (null === $roles) {
            throw new \Exception('Not found any role.');
        }

        $parentTags = $this->factoryService->getParentTags();

        $resource = $this->findOr404($request);

        $locale = $request->getLocale();
        if ($resource->isPrototype()) {
            $formMeta = $this->createForm(MultimediaObjectTemplateMetaType::class, $resource, ['translator' => $this->translationService, 'locale' => $locale]);
        } else {
            $formMeta = $this->createForm(MultimediaObjectMetaType::class, $resource, ['translator' => $this->translationService, 'locale' => $locale]);
        }
        $options = [
            'not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS),
            'translator' => $this->translationService,
            'locale' => $locale,
        ];

        $formPub = $this->createForm(MultimediaObjectPubType::class, $resource, $options);

        //If the 'pudenew' tag is not being used, set the display to 'false'.
        if (!$this->getParameter('show_latest_with_pudenew')) {
            $this->documentManager
                ->getRepository(Tag::class)
                ->findOneByCod('PUDENEW')
                ->setDisplay(false)
            ;
        }
        $pubChannelsTags = $this->factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $this->factoryService->getTagsByCod('PUBDECISIONS', true);

        $jobs = $this->jobService->getNotFinishedJobsByMultimediaObjectId($resource->getId());

        $notMasterProfiles = $this->profileService->getProfiles(null, true, false);

        $template = $resource->isPrototype() ? '_template' : '';

        $activeEditor = $this->checkHasEditor();
        $changePubChannel = $this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL);
        $allBundles = $this->getParameter('kernel.bundles');
        $opencastExists = array_key_exists('PumukitOpencastBundle', $allBundles);

        $allGroups = $this->getAllGroups();

        $showSimplePubTab = $this->getParameter('pumukit_new_admin.show_naked_pub_tab');

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
            'not_change_pub_channel' => !$changePubChannel,
            'groups' => $allGroups,
            'show_simple_pub_tab' => $showSimplePubTab,
        ];
    }

    /**
     * @Template("PumukitNewAdminBundle:MultimediaObject:links.html.twig")
     */
    public function linksAction(MultimediaObject $resource)
    {
        $warningOnUnpublished = $this->getParameter('pumukit.warning_on_unpublished');

        return [
            'mm' => $resource,
            'is_published' => $this->multimediaObjectService->isPublished($resource, 'PUCHWEBTV'),
            'is_hidden' => $this->multimediaObjectService->isHidden($resource, 'PUCHWEBTV'),
            'is_playable' => $this->multimediaObjectService->hasPlayableResource($resource),
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
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($request->request->get('id'))]);
        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $social = $multimediaObject->getEmbeddedSocial();
            if (!$social) {
                $social = new EmbeddedSocial();
            }
            $social->setTwitter($request->request->get('twitter'));
            $social->setEmail($request->request->get('email'));
            $this->documentManager->persist($social);

            $multimediaObject->setEmbeddedSocial($social);

            $this->documentManager->flush();
        }

        return ['mm' => $multimediaObject];
    }

    /**
     * Display the form for editing or update the resource.
     */
    public function updatemetaAction(Request $request)
    {
        $personalScopeRoleCode = $this->personService->getPersonalScopeRoleCode();
        $allGroups = $this->getAllGroups();

        try {
            $personalScopeRole = $this->personService->getPersonalScopeRole();
        } catch (\Exception $e) {
            return new Response($e, Response::HTTP_BAD_REQUEST);
        }

        $roles = $this->personService->getRoles();
        if (null === $roles) {
            throw new \Exception('Not found any role.');
        }

        $parentTags = $this->factoryService->getParentTags();

        $resource = $this->findOr404($request);
        $this->get('session')->set('admin/mms/id', $resource->getId());

        $locale = $request->getLocale();
        if ($resource->isPrototype()) {
            $formMeta = $this->createForm(MultimediaObjectTemplateMetaType::class, $resource, ['translator' => $this->translationService, 'locale' => $locale]);
        } else {
            $formMeta = $this->createForm(MultimediaObjectMetaType::class, $resource, ['translator' => $this->translationService, 'locale' => $locale]);
        }
        $options = [
            'not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS),
            'translator' => $this->translationService,
            'locale' => $locale,
        ];
        $formPub = $this->createForm(MultimediaObjectPubType::class, $resource, $options);

        $pubChannelsTags = $this->factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $this->factoryService->getTagsByCod('PUBDECISIONS', true);

        $changePubChannel = $this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL);

        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $formMeta->handleRequest($request);
            if ($formMeta->isSubmitted() && $formMeta->isValid()) {
                $this->update($resource);

                $this->dispatchUpdate($resource);
                $this->sortedMultimediaObjectService->reorder($resource->getSeries());

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
                'not_change_pub_channel' => !$changePubChannel,
                'groups' => $allGroups,
            ]
        );
    }

    /**
     * Display the form for editing or update the resource.
     */
    public function updatepubAction(Request $request)
    {
        $resource = $this->findOr404($request);

        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $this->factoryService->findSeriesById(null, $resource->getSeries()->getId());
        if (null === $series) {
            throw new \Exception('Series with id '.$request->get('id').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());

        $parentTags = $this->factoryService->getParentTags();

        $this->get('session')->set('admin/mms/id', $resource->getId());

        $locale = $request->getLocale();
        $previousStatus = $resource->getStatus();

        if ($resource->isPrototype()) {
            $formMeta = $this->createForm(MultimediaObjectTemplateMetaType::class, $resource, ['translator' => $this->translationService, 'locale' => $locale]);
        } else {
            $formMeta = $this->createForm(MultimediaObjectMetaType::class, $resource, ['translator' => $this->translationService, 'locale' => $locale]);
        }
        $options = [
            'not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS),
            'translator' => $this->translationService,
            'locale' => $locale,
        ];

        $formPub = $this->createForm(MultimediaObjectPubType::class, $resource, $options);

        $pubChannelsTags = $this->factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $this->factoryService->getTagsByCod('PUBDECISIONS', true);

        $changePubChannel = $this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL);

        $isPrototype = $resource->isPrototype();
        $method = $request->getMethod();

        $formPub->handleRequest($request);
        if ($formPub->isSubmitted() && $formPub->isValid()) {
            //NOTE: If this field is disabled in the form, it sets it to 'null' on the mmobj.
            //Ideally, fix the form instead of working around it like this
            if (null === $resource->getStatus()) {
                $resource->setStatus($previousStatus);
            }

            if ($changePubChannel) {
                $resource = $this->updateTags($request->get('pub_channels', null), 'PUCH', $resource);
            }

            if ($isPrototype) {
                $resource->setStatus(MultimediaObject::STATUS_PROTOTYPE);
            }

            $event = new PublicationSubmitEvent($resource, $request);
            $this->get('event_dispatcher')->dispatch(BackofficeEvents::PUBLICATION_SUBMIT, $event);

            $resource = $this->updateTags($request->get('pub_decisions', null), 'PUDE', $resource);

            $this->update($resource);

            $this->dispatchUpdate($resource);
            $this->sortedMultimediaObjectService->reorder($series);

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

        $personalScopeRoleCode = $this->personService->getPersonalScopeRoleCode();

        try {
            $personalScopeRole = $this->personService->getPersonalScopeRole();
        } catch (\Exception $e) {
            return new Response($e, Response::HTTP_BAD_REQUEST);
        }

        $roles = $this->personService->getRoles();
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
                'not_change_pub_channel' => !$changePubChannel,
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

        try {
            $addedTags = $this->tagService->addTagToMultimediaObject($resource, $request->get('tagId'));
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

        try {
            $deletedTags = $this->tagService->removeTagFromMultimediaObject($resource, $request->get('tagId'));
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
        $repo = $this->documentManager->getRepository(Tag::class);

        $search_text = $request->get('search_text');
        $lang = $request->getLocale();
        $mmId = $request->get('mmId');

        $parent = $repo->findOneBy(['_id' => $request->get('parent')]);
        $parent_path = str_replace('|', '\\|', $parent->getPath());

        $qb = $this->documentManager->createQueryBuilder(Tag::class);
        $children = $qb->addOr($qb->expr()->field('title.'.$lang)->equals(new Regex('.*'.$search_text.'.*', 'i')))
            ->addOr($qb->expr()->field('cod')->equals(new Regex('.*'.$search_text.'.*', 'i')))
            ->addAnd($qb->expr()->field('path')->equals(new Regex($parent_path)))
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
        $this->sortedMultimediaObjectService->reorder($resource->getSeries());

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
            $this->factoryService->deleteMultimediaObject($resource);
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

        foreach ($ids as $id) {
            $resource = $this->find($id);
            if (!$resource || !$this->isUserAllowedToDelete($resource)) {
                continue;
            }

            try {
                $this->factoryService->deleteMultimediaObject($resource);
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

        $response = $this->multimediaObjectService->resetMagicUrl($resource);

        return new Response($response);
    }

    /**
     * Clone action.
     */
    public function cloneAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $seriesId = $resource->getSeries()->getId();

        $this->factoryService->cloneMultimediaObject($resource);

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

        $tagNew = $this->documentManager
            ->getRepository(Tag::class)->findOneByCod('PUDENEW');
        foreach ($ids as $id) {
            $resource = $this->find($id);

            if (!$resource) {
                continue;
            }

            if ($resource->containsTagWithCod('PUDENEW')) {
                $this->tagService->removeTagFromMultimediaObject($resource, $tagNew->getId());
            } else {
                $this->tagService->addTagToMultimediaObject($resource, $tagNew->getId());
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
        $seriesId = $request->get('seriesId', null);
        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $this->factoryService->findSeriesById($seriesId, $sessionId);

        if (!$series) {
            throw $this->createNotFoundException('Page not found!');
        }

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

        $seriesId = $request->get('seriesId', null);
        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $this->factoryService->findSeriesById($seriesId, $sessionId);

        foreach ($ids as $id) {
            $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->find($id);

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

            $this->documentManager->persist($multimediaObject);

            $this->get('pumukitschema.multimediaobject_dispatcher')->dispatchUpdate($multimediaObject);
        }

        $this->documentManager->persist($series);
        $this->documentManager->flush();

        $this->get('session')->remove('admin/mms/cut');

        $this->sortedMultimediaObjectService->reorder($series);

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list'));
    }

    /**
     * Reorder multimedia objects.
     */
    public function reorderAction(Request $request)
    {
        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $this->factoryService->findSeriesById($request->get('id'), $sessionId);

        $series->setSorting($request->get('sorting', Series::SORT_MANUAL));

        $this->documentManager->persist($series);
        $this->documentManager->flush();

        $this->sortedMultimediaObjectService->reorder($series);

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list'));
    }

    /**
     * Sync tags for all multimedia objects of a series.
     */
    public function syncTagsAction(Request $request)
    {
        $all = $request->query->get('all');

        $multimediaObjectRepo = $this->documentManager
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

            $this->tagService->resetCategoriesForCollections($mms, $targetTags);
        } else {
            $parentTags = $this->factoryService->getParentTags();
            $parentTagsToSync = [];

            foreach ($parentTags as $parentTag) {
                if ($parentTag->getDisplay() && !$parentTag->getProperty('hide_in_tag_group')) {
                    $parentTagsToSync[] = $parentTag;
                }
            }

            $this->tagService->syncTagsForCollections(
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
        $repo = $this->documentManager->getRepository(Tag::class);

        $mmId = $request->get('mmId');
        $parent = $repo->findOneBy(['_id' => $request->get('parentId')]);

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
        if ($multimediaObject->isLive()) {
            $broadcasts = $this->embeddedBroadcastService->getAllTypes(true);
        } else {
            $broadcasts = $this->embeddedBroadcastService->getAllTypes();
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
                'descriptioni18n' => $this->specialTranslationService->getI18nEmbeddedBroadcast($embeddedBroadcast),
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
     * @return JsonResponse
     */
    public function userLastRelationAction(Request $request)
    {
        $loggedInUser = $this->getUser();

        $response = null;
        if (!$this->userService->hasPersonalScope($loggedInUser)) {
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
                $response = $this->userService->isUserLastRelation($loggedInUser, $mmId, $personId, $owners, $addGroups);
            } catch (\Exception $e) {
                return new JsonResponse($e->getMessage(), JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }

    /**
     * List the properties of a multimedia object in a modal.
     *
     * @Template("PumukitNewAdminBundle:MultimediaObject:listProperties.html.twig")
     */
    public function listPropertiesAction(MultimediaObject $multimediaObject)
    {
        return ['multimediaObject' => $multimediaObject];
    }

    /**
     * List the external player properties of a multimedia object in a modal.
     *
     * @Security("is_granted('ROLE_ADD_EXTERNAL_PLAYER')")
     * @Template("PumukitNewAdminBundle:MultimediaObject:listExternalPlayer.html.twig")
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

            $data['url'] = urldecode($data['url']);
            $multimediaObject->setProperty('externalplayer', $data['url']);
            $this->documentManager->flush();

            $this->get('pumukitschema.multimediaobject_dispatcher')->dispatchUpdate($multimediaObject);

            return $this->forward('PumukitNewAdminBundle:Track:list', ['multimediaObject' => $multimediaObject]);
        }

        return ['multimediaObject' => $multimediaObject, 'form' => $form->createView()];
    }

    /**
     * List the external player properties of a multimedia object in a modal.
     *
     * @Security("is_granted('ROLE_ADD_EXTERNAL_PLAYER')")
     */
    public function deleteExternalPropertyAction(MultimediaObject $multimediaObject)
    {
        $multimediaObject->removeProperty('externalplayer');
        $this->documentManager->flush();

        $this->get('pumukitschema.multimediaobject_dispatcher')->dispatchUpdate($multimediaObject);

        return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * Show a player of a multimedia object in a modal.
     *
     * @Template("PumukitNewAdminBundle:MultimediaObject:modalPreview.html.twig")
     */
    public function modalPreviewAction(Multimediaobject $multimediaObject)
    {
        return [
            'multimediaObject' => $multimediaObject,
            'is_playable' => $this->multimediaObjectService->hasPlayableResource($multimediaObject),
        ];
    }

    /**
     * Used to update table_mms_status_wrapper via AJAX.
     *
     * @Template("PumukitNewAdminBundle:MultimediaObject:status.html.twig")
     */
    public function statusAction(MultimediaObject $mm, Request $request)
    {
        return ['mm' => $mm];
    }

    /**
     * Overwrite to search criteria with date.
     *
     * @Template("PumukitNewAdminBundle:MultimediaObject:indexAll.html.twig")
     */
    public function indexAllAction(Request $request)
    {
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

        $aRoles = $this->documentManager->getRepository(Role::class)->findAll();
        $aPubChannel = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => 'PUBCHANNELS']);
        $aChannels = $this->documentManager->getRepository(Tag::class)->findBy(['parent.$id' => new ObjectId($aPubChannel->getId())]);

        $multimediaObjectLabel = $this->translationService->trans($this->getParameter('pumukit_new_admin.multimedia_object_label'));
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
            'disable_pudenew' => !$this->getParameter('show_latest_with_pudenew'),
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
                'disable_pudenew' => !$this->getParameter('show_latest_with_pudenew'),
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

        return $this->multimediaObjectSearchService->processMMOCriteria($criteria, $request->getLocale());
    }

    /**
     * Gets the list of resources according to a criteria.
     *
     * @param mixed $criteria
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
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($request->get('id'))]);
        $method = $request->getMethod();
        if (in_array($method, ['POST'])) {
            $multimediaObject->setProperty('paellalayout', $request->get('paellalayout'));
            $this->documentManager->flush();
        }

        return new JsonResponse(['paellalayout' => $multimediaObject->getProperty('paellalayout')]);
    }

    /**
     * Sync selected metadata on all mmobjs of the series.
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
        $locale = $request->getLocale();
        $syncService = $this->container->get('pumukitnewadmin.multimedia_object_sync');

        $tags = $this->documentManager->getRepository(Tag::class)->findBy(
            [
                'metatag' => true,
                'display' => true,
            ],
            ['cod' => 1]
        );
        if (!$tags) {
            throw new \Exception($this->translationService->trans('No tags defined with metatag'));
        }
        $roles = $this->documentManager->getRepository(Role::class)->findBy([], ["name.{$locale}" => 1]);
        if (0 === count($roles)) {
            throw new \Exception($this->translationService->trans('No roles defined'));
        }

        return [
            'fields' => $syncService->getSyncFields(),
            'multimediaObject' => $multimediaObject,
            'tags' => $tags,
            'roles' => $roles,
        ];
    }

    /**
     * @return JsonResponse
     */
    public function updateMultimediaObjectSyncAction(Request $request, MultimediaObject $multimediaObject)
    {
        $message = $this->translationService->trans('Sync metadata was fail.');

        $syncService = $this->container->get('pumukitnewadmin.multimedia_object_sync');
        $multimediaObjects = $syncService->getMultimediaObjectsToSync($multimediaObject);

        $syncFieldsSelected = $request->request->all();
        if (empty($syncFieldsSelected)) {
            $message = $this->translationService->trans('No fields selected to sync');
        }

        if ($multimediaObjects) {
            $syncService->syncMetadata($multimediaObjects, $multimediaObject, $syncFieldsSelected);
            $message = $this->translationService->trans('Sync metadata was done successfully');
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
                    $tag = $this->factoryService->getTagsByCod($cod, false);
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
     * @param mixed|null $newMultimediaObjectId
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

        return $this->paginationService->createDoctrineODMMongoDBAdapter($mmsQueryBuilder, $page);
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

            $person = $this->personService->getPersonFromLoggedInUser($loggedInUser);
            $role = $this->personService->getPersonalScopeRole();
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
        $groupRepo = $this->documentManager->getRepository(Group::class);

        foreach ($addGroups as $addGroup) {
            $groupIdArray = explode('_', $addGroup);
            $groupId = end($groupIdArray);
            $group = $groupRepo->find($groupId);
            if ($group) {
                $this->multimediaObjectService->addGroup($group, $multimediaObject, false);
            }
        }
        foreach ($deleteGroups as $deleteGroup) {
            $groupIdArray = explode('_', $deleteGroup);
            $groupId = end($groupIdArray);
            $group = $groupRepo->find($groupId);
            if ($group) {
                $this->multimediaObjectService->deleteGroup($group, $multimediaObject, false);
            }
        }

        $this->documentManager->flush();
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
        $groupRepo = $this->documentManager->getRepository(Group::class);

        $this->embeddedBroadcastService->updateTypeAndName($type, $multimediaObject, false);
        if (EmbeddedBroadcast::TYPE_PASSWORD === $type) {
            $this->embeddedBroadcastService->updatePassword($password, $multimediaObject, false);
        } elseif (EmbeddedBroadcast::TYPE_GROUPS === $type) {
            foreach ($addGroups as $addGroup) {
                $groupIdArray = explode('_', $addGroup);
                $groupId = end($groupIdArray);
                $group = $groupRepo->find($groupId);
                if ($group) {
                    $this->embeddedBroadcastService->addGroup($group, $multimediaObject, false);
                }
            }
            foreach ($deleteGroups as $deleteGroup) {
                $groupIdArray = explode('_', $deleteGroup);
                $groupId = end($groupIdArray);
                $group = $groupRepo->find($groupId);
                if ($group) {
                    $this->embeddedBroadcastService->deleteGroup($group, $multimediaObject, false);
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

        $this->documentManager->flush();
    }

    private function getResourceGroups($groups = [])
    {
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
            $addGroupsIds[] = new ObjectId($group->getId());
        }
        $allGroups = $this->getAllGroups();
        foreach ($allGroups as $group) {
            $allGroupsIds[] = new ObjectId($group->getId());
        }
        $groupsToDelete = $this->groupService->findByIdNotInOf($addGroupsIds, $allGroupsIds);
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

        $globalScope = $this->userService->hasGlobalScope($loggedInUser);
        if ($globalScope) {
            return true;
        }

        $userInOwners = false;

        $personRepo = $this->documentManager->getRepository(Person::class);
        foreach ((array) $multimediaObject->getProperty('owners') as $owner) {
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
