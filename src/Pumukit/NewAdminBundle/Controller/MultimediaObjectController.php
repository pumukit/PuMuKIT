<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\NewAdminBundle\Event\BackofficeEvents;
use Pumukit\NewAdminBundle\Event\PublicationSubmitEvent;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectMetaType;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectPubType;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectTemplateMetaType;
use Pumukit\NewAdminBundle\Services\MultimediaObjectSearchService;
use Pumukit\NewAdminBundle\Services\MultimediaObjectSyncService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\EmbeddedSocial;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\TagInterface;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Event\SchemaEvents;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\MultimediaObjectEventDispatcherService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\SortedMultimediaObjectsService;
use Pumukit\SchemaBundle\Services\SpecialTranslationService;
use Pumukit\SchemaBundle\Services\TagService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class MultimediaObjectController extends SortableAdminController
{
    public static $resourceName = 'mms';
    public static $repoName = MultimediaObject::class;

    /** @var RequestStack */
    private $requestStack;
    /** @var MultimediaObjectSyncService */
    private $multimediaObjectSyncService;
    /** @var MultimediaObjectSearchService */
    private $multimediaObjectSearchService;
    /** @var PersonService */
    private $personService;
    /** @var SortedMultimediaObjectsService */
    private $sortedMultimediaObjectService;
    /** @var JobService */
    private $jobService;
    /** @var ProfileService */
    private $profileService;
    /** @var MultimediaObjectService */
    private $multimediaObjectService;

    /** @var TagService */
    private $tagService;

    /** @var EmbeddedBroadcastService */
    private $embeddedBroadcastService;
    /** @var SpecialTranslationService */
    private $specialTranslatorService;

    /** @var MultimediaObjectEventDispatcherService */
    private $pumukitSchemaMultimediaObjectDispatcher;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var RouterInterface */
    private $router;

    private $showLatestWithPudeNew;
    private $pumukitNewAdminShowNakedPubTab;
    private $warningOnUnpublished;
    private $kernelBundles;
    private $pumukitNewAdminMultimediaObjectLabel;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        UserService $userService,
        TranslatorInterface $translator,
        RequestStack $requestStack,
        MultimediaObjectSyncService $multimediaObjectSyncService,
        MultimediaObjectSearchService $multimediaObjectSearchService,
        PersonService $personService,
        SortedMultimediaObjectsService $sortedMultimediaObjectService,
        JobService $jobService,
        ProfileService $profileService,
        SessionInterface $session,
        MultimediaObjectService $multimediaObjectService,
        TagService $tagService,
        EmbeddedBroadcastService $embeddedBroadcastService,
        SpecialTranslationService $specialTranslatorService,
        MultimediaObjectEventDispatcherService $pumukitSchemaMultimediaObjectDispatcher,
        EventDispatcherInterface $eventDispatcher,
        RouterInterface $router,
        $showLatestWithPudeNew,
        $pumukitNewAdminShowNakedPubTab,
        $warningOnUnpublished,
        $kernelBundles,
        $pumukitNewAdminMultimediaObjectLabel
    ) {
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService, $session, $translator);
        $this->requestStack = $requestStack;
        $this->multimediaObjectSyncService = $multimediaObjectSyncService;
        $this->multimediaObjectSearchService = $multimediaObjectSearchService;
        $this->personService = $personService;
        $this->sortedMultimediaObjectService = $sortedMultimediaObjectService;
        $this->jobService = $jobService;
        $this->profileService = $profileService;
        $this->multimediaObjectService = $multimediaObjectService;
        $this->tagService = $tagService;
        $this->embeddedBroadcastService = $embeddedBroadcastService;
        $this->specialTranslatorService = $specialTranslatorService;
        $this->pumukitSchemaMultimediaObjectDispatcher = $pumukitSchemaMultimediaObjectDispatcher;
        $this->eventDispatcher = $eventDispatcher;
        $this->router = $router;
        $this->showLatestWithPudeNew = $showLatestWithPudeNew;
        $this->pumukitNewAdminShowNakedPubTab = $pumukitNewAdminShowNakedPubTab;
        $this->warningOnUnpublished = $warningOnUnpublished;
        $this->kernelBundles = $kernelBundles;
        $this->pumukitNewAdminMultimediaObjectLabel = $pumukitNewAdminMultimediaObjectLabel;
    }

    /**
     * @Template("@PumukitNewAdmin/MultimediaObject/index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $session = $this->session;

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
            'disable_pudenew' => !$this->showLatestWithPudeNew,
        ];
    }

    public function shortenerAction($id)
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['id' => $id]);
        if (!$multimediaObject) {
            $template = '@PumukitNewAdmin/MultimediaObject/404notfound.html.twig';
            $options = ['id' => $id];

            return new Response($this->renderView($template, $options), 404);
        }
        $this->updateSession($multimediaObject);

        return $this->redirectToRoute('pumukitnewadmin_mms_index', ['id' => $multimediaObject->getSeries()->getId()]);
    }

    public function createAction(Request $request)
    {
        $session = $this->session;

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
     * @Template("@PumukitNewAdmin/MultimediaObject/show.html.twig")
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
     * @Template("@PumukitNewAdmin/MultimediaObject/edit.html.twig")
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
            $formMeta = $this->createForm(MultimediaObjectTemplateMetaType::class, $resource, ['translator' => $this->translator, 'locale' => $locale]);
        } else {
            $formMeta = $this->createForm(MultimediaObjectMetaType::class, $resource, ['translator' => $this->translator, 'locale' => $locale]);
        }
        $options = [
            'not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS),
            'translator' => $this->translator,
            'locale' => $locale,
        ];

        $formPub = $this->createForm(MultimediaObjectPubType::class, $resource, $options);

        //If the 'pudenew' tag is not being used, set the display to 'false'.
        if (!$this->showLatestWithPudeNew) {
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
        $allBundles = $this->kernelBundles;
        $opencastExists = array_key_exists('PumukitOpencastBundle', $allBundles);

        $allGroups = $this->getAllGroups();

        $showSimplePubTab = $this->pumukitNewAdminShowNakedPubTab;

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
     * @Template("@PumukitNewAdmin/MultimediaObject/links.html.twig")
     */
    public function linksAction(MultimediaObject $resource)
    {
        return [
            'mm' => $resource,
            'is_published' => $this->multimediaObjectService->isPublished($resource, 'PUCHWEBTV'),
            'is_hidden' => $this->multimediaObjectService->isHidden($resource, 'PUCHWEBTV'),
            'is_playable' => $this->multimediaObjectService->hasPlayableResource($resource),
            'warning_on_unpublished' => $this->warningOnUnpublished,
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/MultimediaObject/updatesocial.html.twig")
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
        $this->session->set('admin/mms/id', $resource->getId());

        $locale = $request->getLocale();
        if ($resource->isPrototype()) {
            $formMeta = $this->createForm(MultimediaObjectTemplateMetaType::class, $resource, ['translator' => $this->translator, 'locale' => $locale]);
        } else {
            $formMeta = $this->createForm(MultimediaObjectMetaType::class, $resource, ['translator' => $this->translator, 'locale' => $locale]);
        }
        $options = [
            'not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS),
            'translator' => $this->translator,
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
            '@PumukitNewAdmin/MultimediaObject/edit.html.twig',
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

    public function updatepubAction(Request $request)
    {
        $resource = $this->findOr404($request);

        $sessionId = $this->session->get('admin/series/id', null);
        $series = $this->factoryService->findSeriesById(null, $resource->getSeries()->getId());
        if (null === $series) {
            throw new \Exception('Series with id '.$request->get('id').' or with session id '.$sessionId.' not found.');
        }
        $this->session->set('admin/series/id', $series->getId());

        $parentTags = $this->factoryService->getParentTags();

        $this->session->set('admin/mms/id', $resource->getId());

        $locale = $request->getLocale();
        $previousStatus = $resource->getStatus();

        if ($resource->isPrototype()) {
            $formMeta = $this->createForm(MultimediaObjectTemplateMetaType::class, $resource, ['translator' => $this->translator, 'locale' => $locale]);
        } else {
            $formMeta = $this->createForm(MultimediaObjectMetaType::class, $resource, ['translator' => $this->translator, 'locale' => $locale]);
        }
        $options = [
            'not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS),
            'translator' => $this->translator,
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
            $this->eventDispatcher->dispatch($event, BackofficeEvents::PUBLICATION_SUBMIT);

            $resource = $this->updateTags($request->get('pub_decisions', null), 'PUDE', $resource);

            $this->update($resource);

            $this->dispatchUpdate($resource);
            $this->sortedMultimediaObjectService->reorder($series);

            $mms = $this->getListMultimediaObjects($series);
            if (false === strpos($request->server->get('HTTP_REFERER'), 'mmslist')) {
                return $this->render(
                    '@PumukitNewAdmin/MultimediaObject/list.html.twig',
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
            '@PumukitNewAdmin/MultimediaObject/edit.html.twig',
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
     * @Template("@PumukitNewAdmin/MultimediaObject/listtagsajax.html.twig")
     */
    public function getChildrenTagAction(TagInterface $tag, Request $request)
    {
        return [
            'nodes' => $tag->getChildren(),
            'parent' => $tag->getId(),
            'mmId' => $request->get('mm_id'),
            'block_tag' => $request->get('tag_id'),
        ];
    }

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

    public function searchTagAction(Request $request)
    {
        $repo = $this->documentManager->getRepository(Tag::class);

        $search_text = $request->get('search_text');
        $search_text = $request->query->get('search_text');

        $lang = $request->getLocale();
        $mmId = $request->get('mmId');

        $parent = $repo->findOneBy(['_id' => $request->get('parent')]);
        $parent_path = str_replace('|', '\\|', $parent->getPath());

        $qb = $this->documentManager->createQueryBuilder(Tag::class);
        $children = $qb
            ->addOr($qb->expr()->field('title.'.$lang)->equals(new Regex('.*'.$search_text.'.*', 'i')))
            ->addOr($qb->expr()->field('cod')->equals(new Regex('.*'.$search_text.'.*', 'i')))
            ->addAnd($qb->expr()->field('path')->equals(new Regex($parent_path)))
            ->getQuery()
            ->execute()
        ;
        if (is_object($children)) {
            $result = $children->toArray();
        } else {
            $result = $children;
        }

        if (!$result) {
            return $this->render(
                '@PumukitNewAdmin/MultimediaObject/listtagsajaxnone.html.twig',
                ['mmId' => $mmId, 'parentId' => $parent->getId()]
            );
        }

        foreach ($children as $tag) {
            $result = $this->getAllParents($tag, $result, $parent->getId());
        }

        usort(
            $result,
            static function (TagInterface $x, TagInterface $y) {
                return strcasecmp($x->getCod(), $y->getCod());
            }
        );

        return $this->render(
            '@PumukitNewAdmin/MultimediaObject/listtagsajax.html.twig',
            ['nodes' => $result, 'mmId' => $mmId, 'block_tag' => $parent->getId(), 'parent' => $parent, 'search_text' => $search_text]
        );
    }

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

        if ($resourceId === $this->session->get('admin/mms/id')) {
            $this->session->remove('admin/mms/id');
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
            if ($id === $this->session->get('admin/mms/id')) {
                $this->session->remove('admin/mms/id');
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list'));
    }

    public function generateMagicUrlAction(Request $request)
    {
        $resource = $this->findOr404($request);

        $response = $this->multimediaObjectService->resetMagicUrl($resource);

        return new Response($response);
    }

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

    public function invertAnnounceAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $tagNew = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => 'PUDENEW']);
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

    public function listAction(Request $request)
    {
        $seriesId = $request->get('seriesId', null);
        $sessionId = $this->session->get('admin/series/id', null);
        $series = $this->factoryService->findSeriesById($seriesId, $sessionId);

        if (!$series) {
            throw $this->createNotFoundException('Page not found!');
        }

        $mms = $this->getListMultimediaObjects($series, $request->get('newMmId', null));

        $update_session = true;
        foreach ($mms as $mm) {
            if ($mm->getId() == $this->session->get('admin/mms/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $this->session->remove('admin/mms/id');
        }

        return $this->render(
            '@PumukitNewAdmin/MultimediaObject/list.html.twig',
            [
                'series' => $series,
                'mms' => $mms,
            ]
        );
    }

    public function cutAction(Request $request)
    {
        $ids = $request->get('ids');
        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }
        $this->session->set('admin/mms/cut', $ids);

        return new JsonResponse($ids);
    }

    public function pasteAction(Request $request)
    {
        if (!($this->session->has('admin/mms/cut'))) {
            throw new \Exception('Not found any multimedia object to paste.');
        }

        $ids = $this->session->get('admin/mms/cut');

        $seriesId = $request->get('seriesId', null);
        $sessionId = $this->session->get('admin/series/id', null);
        $series = $this->factoryService->findSeriesById($seriesId, $sessionId);

        foreach ($ids as $id) {
            $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->find($id);

            if (!$multimediaObject) {
                continue;
            }

            if ($id === $this->session->get('admin/mms/id')) {
                $this->session->remove('admin/mms/id');
            }
            $multimediaObject->setSeries($series);
            if (Series::SORT_MANUAL === $series->getSorting()) {
                $multimediaObject->setRank(-1);
            }

            $this->documentManager->persist($multimediaObject);

            $this->pumukitSchemaMultimediaObjectDispatcher->dispatchUpdate($multimediaObject);
        }

        $this->documentManager->persist($series);
        $this->documentManager->flush();

        $this->session->remove('admin/mms/cut');

        $this->sortedMultimediaObjectService->reorder($series);

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list'));
    }

    public function reorderAction(Request $request)
    {
        $sessionId = $this->session->get('admin/series/id', null);
        $series = $this->factoryService->findSeriesById($request->get('id'), $sessionId);

        $series->setSorting($request->get('sorting', Series::SORT_MANUAL));

        $this->documentManager->persist($series);
        $this->documentManager->flush();

        $this->sortedMultimediaObjectService->reorder($series);

        return $this->redirect($this->generateUrl('pumukitnewadmin_mms_list'));
    }

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
            ->getQuery()
            ->execute()
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

    public function reloadTagsAction(Request $request)
    {
        $repo = $this->documentManager->getRepository(Tag::class);

        $mmId = $request->get('mmId');
        $parent = $repo->findOneBy(['_id' => $request->get('parentId')]);

        return $this->render(
            '@PumukitNewAdmin/MultimediaObject/listtagsajax.html.twig',
            ['nodes' => $parent->getChildren(), 'mmId' => $mmId, 'block_tag' => $parent->getId(), 'parent' => 'root']
        );
    }

    /**
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

    public function getGroupsAction(Request $request)
    {
        $multimediaObject = $this->findOr404($request);
        $groups = $this->getResourceGroups($multimediaObject->getGroups());

        return new JsonResponse($groups);
    }

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
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "id"})
     * @Template("@PumukitNewAdmin/MultimediaObject/updatebroadcast.html.twig")
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
                'descriptioni18n' => $this->specialTranslatorService->getI18nEmbeddedBroadcast($embeddedBroadcast),
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
     * @Template("@PumukitNewAdmin/MultimediaObject/listProperties.html.twig")
     */
    public function listPropertiesAction(MultimediaObject $multimediaObject)
    {
        return ['multimediaObject' => $multimediaObject];
    }

    /**
     * @Security("is_granted('ROLE_ADD_EXTERNAL_PLAYER')")
     * @Template("@PumukitNewAdmin/MultimediaObject/listExternalPlayer.html.twig")
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

            $this->pumukitSchemaMultimediaObjectDispatcher->dispatchUpdate($multimediaObject);

            return $this->forward('PumukitNewAdminBundle:Track:list', ['multimediaObject' => $multimediaObject]);
        }

        return ['multimediaObject' => $multimediaObject, 'form' => $form->createView()];
    }

    /**
     * @Security("is_granted('ROLE_ADD_EXTERNAL_PLAYER')")
     */
    public function deleteExternalPropertyAction(MultimediaObject $multimediaObject)
    {
        $multimediaObject->removeProperty('externalplayer');
        $this->documentManager->flush();

        $this->pumukitSchemaMultimediaObjectDispatcher->dispatchUpdate($multimediaObject);

        return $this->redirect($this->generateUrl('pumukitnewadmin_track_list', ['id' => $multimediaObject->getId()]));
    }

    /**
     * @Template("@PumukitNewAdmin/MultimediaObject/modalPreview.html.twig")
     */
    public function modalPreviewAction(Multimediaobject $multimediaObject)
    {
        return [
            'multimediaObject' => $multimediaObject,
            'is_playable' => $this->multimediaObjectService->hasPlayableResource($multimediaObject),
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/MultimediaObject/status.html.twig")
     */
    public function statusAction(MultimediaObject $mm, Request $request)
    {
        return ['mm' => $mm];
    }

    /**
     * @Template("@PumukitNewAdmin/MultimediaObject/indexAll.html.twig")
     */
    public function indexAllAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $resources = $this->getResources($request, $criteria);

        $update_session = true;
        foreach ($resources as $mm) {
            if ($mm->getId() == $this->session->get('admin/mmslist/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $this->session->remove('admin/mmslist/id');
        }

        $aRoles = $this->documentManager->getRepository(Role::class)->findAll();
        $aPubChannel = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => 'PUBCHANNELS']);
        $aChannels = $this->documentManager->getRepository(Tag::class)->findBy(['parent.$id' => new ObjectId($aPubChannel->getId())]);

        $multimediaObjectLabel = $this->translator->trans($this->pumukitNewAdminMultimediaObjectLabel);
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
            'disable_pudenew' => !$this->showLatestWithPudeNew,
            'multimedia_object_label' => $multimediaObjectLabel,
        ];
    }

    public function listAllAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $resources = $this->getResources($request, $criteria);

        return $this->render(
            '@PumukitNewAdmin/MultimediaObject/listAll.html.twig',
            [
                'mms' => $resources,
                'disable_pudenew' => !$this->showLatestWithPudeNew,
            ]
        );
    }

    public function getCriteria($criteria)
    {
        $request = $this->requestStack->getCurrentRequest();
        $requestCriteria = $request->get('criteria', []);

        if (array_key_exists('reset', $requestCriteria)) {
            $this->session->remove('admin/'.$this->getResourceName().'/criteria');
        } elseif ($requestCriteria) {
            $this->session->set('admin/'.$this->getResourceName().'/criteria', $requestCriteria);
        }
        $requestCriteria = $this->session->get('admin/'.$this->getResourceName().'/criteria', []);

        return $this->multimediaObjectSearchService->processMMOCriteria($requestCriteria, $request->getLocale());
    }

    public function getResources(Request $request, $criteria)
    {
        $sorting = $this->getSorting($request, $this->getResourceName());
        $session = $this->session;
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

    public function getSorting(Request $request = null, $session_namespace = null): array
    {
        $session = $this->session;

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

    public function getResourceName(): string
    {
        $request = $this->requestStack->getMasterRequest();
        $sRoute = $request->get('_route');

        return (false === strpos($sRoute, 'all')) ? 'mms' : 'mmslist';
    }

    public function updatePropertyAction(Request $request): JsonResponse
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
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "id"})
     * @Template("@PumukitNewAdmin/MultimediaObject/modalsyncmetadata.html.twig")
     */
    public function modalSyncMedatadaAction(Request $request, MultimediaObject $multimediaObject)
    {
        $locale = $request->getLocale();
        $syncService = $this->multimediaObjectSyncService;

        $tags = $this->documentManager->getRepository(Tag::class)->findBy(
            [
                'metatag' => true,
                'display' => true,
            ],
            ['cod' => 1]
        );
        if (!$tags) {
            throw new \Exception($this->translator->trans('No tags defined with metatag'));
        }
        $roles = $this->documentManager->getRepository(Role::class)->findBy([], ["name.{$locale}" => 1]);
        if (0 === count($roles)) {
            throw new \Exception($this->translator->trans('No roles defined'));
        }

        return [
            'fields' => $syncService->getSyncFields(),
            'multimediaObject' => $multimediaObject,
            'tags' => $tags,
            'roles' => $roles,
        ];
    }

    public function updateMultimediaObjectSyncAction(Request $request, MultimediaObject $multimediaObject): JsonResponse
    {
        $message = $this->translator->trans('Sync metadata was fail.');

        $syncService = $this->multimediaObjectSyncService;
        $multimediaObjects = $syncService->getMultimediaObjectsToSync($multimediaObject);

        $syncFieldsSelected = $request->request->all();
        if (empty($syncFieldsSelected)) {
            $message = $this->translator->trans('No fields selected to sync');
        }

        if ($multimediaObjects) {
            $syncService->syncMetadata($multimediaObjects, $multimediaObject, $syncFieldsSelected);
            $message = $this->translator->trans('Sync metadata was done successfully');
        }

        return new JsonResponse($message, JsonResponse::HTTP_OK);
    }

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

    protected function getListMultimediaObjects(Series $series, $newMultimediaObjectId = null)
    {
        $session = $this->session;
        $page = $session->get('admin/mms/page', 1);

        $maxPerPage = $session->get('admin/mms/paginate', 10);

        $sorting = ['rank' => 'asc'];

        $mmsQueryBuilder = $this->documentManager->getRepository(MultimediaObject::class)->getQueryBuilderOrderedBy($series, $sorting);

        return $this->paginationService->createDoctrineODMMongoDBAdapter($mmsQueryBuilder, $page);
    }

    protected function dispatchUpdate($multimediaObject)
    {
        $event = new MultimediaObjectEvent($multimediaObject);
        $this->eventDispatcher->dispatch($event, SchemaEvents::MULTIMEDIAOBJECT_UPDATE);
    }

    //Workaround function to check if the VideoEditorBundle is installed.
    protected function checkHasEditor()
    {
        $routes = $this->router->getRouteCollection()->all();

        return array_key_exists('pumukit_videoeditor_index', $routes);
    }

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

    private function getAllParents(TagInterface $element, array $tags, string $top_parent): array
    {
        if ($element->getId() === $top_parent) {
            return $tags;
        }

        $parent = $element->getParent();
        foreach ($tags as $tag) {
            if ($parent === $tag) {
                return $tags;
            }
        }

        if (2 !== (int) $parent->getLevel()) {
            $tags[] = $parent;
        }

        return $this->getAllParents($parent, $tags, $top_parent);
    }

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
                '@PumukitNewAdmin/MultimediaObject/list.html.twig',
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
        $session = $this->session;
        $paginate = $session->get('admin/mms/paginate', 10);

        $page = (int) ceil($mm->getRank() / $paginate);
        if ($page < 1) {
            $page = 1;
        }

        $session->set('admin/mms/page', $page);
        $session->set('admin/mms/id', $mm->getId());
    }
}
