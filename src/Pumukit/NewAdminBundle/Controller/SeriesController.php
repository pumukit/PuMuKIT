<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pagerfanta\Pagerfanta;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectTemplateMetaType;
use Pumukit\NewAdminBundle\Form\Type\SeriesType;
use Pumukit\NewAdminBundle\Services\SeriesSearchService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\SeriesService;
use Pumukit\SchemaBundle\Services\SortedMultimediaObjectsService;
use Pumukit\SchemaBundle\Services\TagService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class SeriesController extends AdminController
{
    public static $resourceName = 'series';
    public static $repoName = Series::class;

    /** @var EmbeddedBroadcastService */
    protected $embeddedBroadcastService;
    /** @var TranslatorInterface */
    protected $translationService;
    /** @var SortedMultimediaObjectsService */
    protected $sortedMultimediaObjectService;
    /** @var PersonService */
    protected $personService;
    /** @var TagService */
    protected $tagService;
    /** @var SeriesService */
    protected $seriesService;
    /** @var SeriesSearchService */
    protected $seriesSearchService;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        UserService $userService,
        EmbeddedBroadcastService $embeddedBroadcastService,
        TranslatorInterface $translationService,
        SortedMultimediaObjectsService $sortedMultimediaObjectService,
        PersonService $personService,
        TagService $tagService,
        SeriesService $seriesService,
        SeriesSearchService $seriesSearchService
    ) {
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService);
        $this->embeddedBroadcastService = $embeddedBroadcastService;
        $this->translationService = $translationService;
        $this->sortedMultimediaObjectService = $sortedMultimediaObjectService;
        $this->personService = $personService;
        $this->tagService = $tagService;
        $this->seriesService = $seriesService;
        $this->seriesSearchService = $seriesSearchService;
    }

    /**
     * Overwrite to search criteria with date.
     *
     * @Template("PumukitNewAdminBundle:Series:index.html.twig")
     *
     * @return array
     */
    public function indexAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $resources = $this->getResources($request, $criteria);

        $update_session = true;
        foreach ($resources as $series) {
            if ($series->getId() == $this->get('session')->get('admin/series/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $this->get('session')->remove('admin/series/id');
        }

        return [
            'series' => $resources,
            'disable_pudenew' => !$this->container->getParameter('show_latest_with_pudenew'),
        ];
    }

    /**
     * List action.
     *
     * @return array
     * @Template("PumukitNewAdminBundle:Series:list.html.twig")
     */
    public function listAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $selectedSeriesId = $request->get('selectedSeriesId', null);
        $resources = $this->getResources($request, $criteria, $selectedSeriesId);

        return ['series' => $resources];
    }

    /**
     * Create new resource.
     *
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $series = $this->factoryService->createSeries($this->getUser());
        $this->get('session')->set('admin/series/id', $series->getId());

        return new JsonResponse(['seriesId' => $series->getId()]);
    }

    /**
     * @param string $id
     *
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function cloneAction($id)
    {
        $series = $this->documentManager->getRepository(Series::class)->findOneBy(['_id' => new ObjectId($id)]);
        if (!$series) {
            throw new \Exception($this->translationService->trans('No series found with ID').' '.$id);
        }

        try {
            $this->factoryService->cloneSeries($series);

            return $this->redirectToRoute('pumukitnewadmin_series_list');
        } catch (\Exception $exception) {
            throw new \Exception($this->translationService->trans('Error while cloning series ').$exception->getMessage());
        }
    }

    /**
     * @return array
     * @Template("PumukitNewAdminBundle:Series:links.html.twig")
     */
    public function linksAction(Series $resource)
    {
        return [
            'series' => $resource,
        ];
    }

    /**
     * Display the form for editing or update the resource.
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function updateAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $this->get('session')->set('admin/series/id', $request->get('id'));

        $locale = $request->getLocale();
        $disablePudenew = !$this->container->getParameter('show_latest_with_pudenew');
        $form = $this->createForm(SeriesType::class, $resource, ['translator' => $this->translationService, 'locale' => $locale, 'disable_PUDENEW' => $disablePudenew]);

        $method = $request->getMethod();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $this->update($resource);
            $this->get('pumukitschema.series_dispatcher')->dispatchUpdate($resource);
            if (Series::SORT_MANUAL !== $resource->getSorting()) {
                $this->sortedMultimediaObjectService->reorder($resource);
            }

            $criteria = $this->getCriteria($request->get('criteria', []));
            $resources = $this->getResources($request, $criteria, $resource->getId());

            return $this->render(
                'PumukitNewAdminBundle:Series:list.html.twig',
                ['series' => $resources]
            );
        }

        // EDIT MULTIMEDIA OBJECT TEMPLATE CONTROLLER SOURCE CODE

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
        $mmtemplate = $this->factoryService->getMultimediaObjectPrototype($resource);

        $locale = $request->getLocale();

        $formMeta = $this->createForm(MultimediaObjectTemplateMetaType::class, $mmtemplate, ['translator' => $this->translationService, 'locale' => $locale]);

        $pubDecisionsTags = $this->factoryService->getTagsByCod('PUBDECISIONS', true);

        //These fields are form fields that are rendered separately, so they should be 'excluded' from the generic foreach.
        $exclude_fields = [];
        $show_later_fields = ['pumukitnewadmin_series_i18n_header', 'pumukitnewadmin_series_i18n_footer', 'pumukitnewadmin_series_i18n_line2', 'pumukitnewadmin_series_template', 'pumukitnewadmin_series_sorting', 'pumukitnewadmin_series_series_style'];
        $showSeriesTypeTab = $this->container->hasParameter('pumukit.use_series_channels') && $this->container->getParameter('pumukit.use_series_channels');
        if (!$showSeriesTypeTab) {
            $exclude_fields[] = 'pumukitnewadmin_series_series_type';
        }

        return $this->render(
            'PumukitNewAdminBundle:Series:update.html.twig',
            [
                'series' => $resource,
                'form' => $form->createView(),
                'mmtemplate' => $mmtemplate,
                'form_meta' => $formMeta->createView(),
                'roles' => $roles,
                'personal_scope_role' => $personalScopeRole,
                'personal_scope_role_code' => $personalScopeRoleCode,
                'pub_decisions' => $pubDecisionsTags,
                'parent_tags' => $parentTags,
                'exclude_fields' => $exclude_fields,
                'show_later_fields' => $show_later_fields,
                'template' => '_template',
                'groups' => $allGroups,
            ]
        );
    }

    /**
     * @return Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request)
    {
        $series = $this->findOr404($request);
        if (!$this->isUserAllowedToDelete($series)) {
            return new Response('You don\'t have enough permissions to delete this series. Contact your administrator.', Response::HTTP_FORBIDDEN);
        }

        $seriesId = $series->getId();

        $seriesSessionId = $this->get('session')->get('admin/mms/id');
        if ($seriesId === $seriesSessionId) {
            $this->get('session')->remove('admin/series/id');
        }

        $mmSessionId = $this->get('session')->get('admin/mms/id');
        if ($mmSessionId) {
            $mm = $this->factoryService->findMultimediaObjectById($mmSessionId);
            if ($seriesId === $mm->getSeries()->getId()) {
                $this->get('session')->remove('admin/mms/id');
            }
        }

        try {
            $this->factoryService->deleteSeries($series);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->redirectToRoute('pumukitnewadmin_series_list');
    }

    /**
     * Generate Magic Url action.
     *
     * @return Response
     */
    public function generateMagicUrlAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $response = $this->seriesService->resetMagicUrl($resource);

        return new Response($response);
    }

    /**
     * Batch delete action
     * Overwrite to delete multimedia objects inside series.
     *
     * @return Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $deleteSeriesCount = 0;
        foreach ($ids as $id) {
            $series = $this->find($id);
            if (!$series || !$this->isUserAllowedToDelete($series)) {
                continue;
            }
            $seriesId = $series->getId();

            $seriesSessionId = $this->get('session')->get('admin/mms/id');
            if ($seriesId === $seriesSessionId) {
                $this->get('session')->remove('admin/series/id');
            }

            $mmSessionId = $this->get('session')->get('admin/mms/id');
            if ($mmSessionId) {
                $mm = $this->factoryService->findMultimediaObjectById($mmSessionId);
                if ($seriesId === $mm->getSeries()->getId()) {
                    $this->get('session')->remove('admin/mms/id');
                }
            }

            try {
                $this->factoryService->deleteSeries($series);
                ++$deleteSeriesCount;
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }

        if (0 == $deleteSeriesCount) {
            return new Response('0 series deleted', Response::HTTP_BAD_REQUEST);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list', []));
    }

    /**
     * Batch invert announce selected.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function invertAnnounceAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        foreach ($ids as $id) {
            $resource = $this->find($id);

            if (!$resource) {
                continue;
            }

            if ($resource->getAnnounce()) {
                $resource->setAnnounce(false);
            } else {
                $resource->setAnnounce(true);
            }
            $this->documentManager->persist($resource);
        }
        $this->documentManager->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list'));
    }

    /**
     * Change publication form.
     *
     * @return array
     * @Template("PumukitNewAdminBundle:Series:changepub.html.twig")
     */
    public function changePubAction(Request $request)
    {
        $series = $this->findOr404($request);

        $mmStatus = [
            'published' => MultimediaObject::STATUS_PUBLISHED,
            'blocked' => MultimediaObject::STATUS_BLOCKED,
            'hidden' => MultimediaObject::STATUS_HIDDEN,
        ];

        $multimediaObjects = $this->documentManager->getRepository(MultimediaObject::class)->findWithoutPrototype($series);

        $pubChannels = $this->factoryService->getTagsByCod('PUBCHANNELS', true);

        foreach ($pubChannels as $key => $pubTag) {
            if ($pubTag->getProperty('hide_in_tag_group')) {
                unset($pubChannels[$key]);
            }
        }

        return [
            'series' => $series,
            'mm_status' => $mmStatus,
            'pub_channels' => $pubChannels,
            'multimediaObjects' => $multimediaObjects,
        ];
    }

    /**
     * Update publication form.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updatePubAction(Request $request)
    {
        if ('POST' === $request->getMethod()) {
            $values = $request->get('values');
            if ('string' === gettype($values)) {
                $values = json_decode($values, true);
            }

            $this->modifyMultimediaObjectsStatus($values);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list'));
    }

    public function getCriteria($criteria)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        //$criteria = $request->get('criteria', []);

        $emptySeries = [];
        if ($request->query->has('empty_series') || $this->get('session')->has('admin/series/empty_series')) {
            $this->get('session')->set('admin/series/empty_series', true);

            $mmObjColl = $this->documentManager->getDocumentCollection(MultimediaObject::class);
            $pipeline = [
                ['$group' => ['_id' => '$series', 'count' => ['$sum' => 1]]],
                ['$match' => ['count' => 1]],
            ];
            $allSeries = $mmObjColl->aggregate($pipeline, ['cursor' => []])->toArray();
            foreach ($allSeries as $series) {
                $emptySeries[] = $series['_id'];
            }
            $criteria['playlist.multimedia_objects'] = ['$size' => 0];
            $criteria = array_merge($criteria, ['_id' => ['$in' => array_values($emptySeries)]]);
            $this->get('session')->set('admin/series/criteria', $criteria);
        }

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/series/criteria');
            $this->get('session')->remove('admin/series/empty_series');
            $this->get('session')->remove('admin/series/sort');
        } elseif ($criteria) {
            $this->get('session')->set('admin/series/criteria', $criteria);
        }

        $criteria = $this->get('session')->get('admin/series/criteria', []);

        return $this->seriesSearchService->processCriteria($criteria, false, $request->getLocale());
    }

    /**
     * @param Request    $request
     * @param mixed|null $session_namespace
     *
     * @return array
     */
    public function getSorting(Request $request = null, $session_namespace = null)
    {
        $session = $this->get('session');

        if (!$session->get('admin/series/sort') && $session->get('admin/series/criteria')) {
            $session->set('admin/series/type', 'score');
            $session->set('admin/series/sort', 'textScore');
        }

        if ($sorting = $request->get('sorting')) {
            $session->set('admin/series/type', current($sorting));
            $session->set('admin/series/sort', key($sorting));
        }

        $value = $session->get('admin/series/type', 'desc');
        $key = $session->get('admin/series/sort', 'public_date');

        if ('title' == $key) {
            $key .= '.'.$request->getLocale();
        }

        return [$key => $value];
    }

    /**
     * Gets the list of resources according to a criteria.
     *
     * @param array         $criteria
     * @param \MongoId|null $selectedSeriesId
     *
     * @return array|mixed|Pagerfanta
     */
    public function getResources(Request $request, $criteria, $selectedSeriesId = null)
    {
        $sorting = $this->getSorting($request);
        $session = $this->get('session');
        $session_namespace = 'admin/series';
        //Added TYPE_SERIES to criteria (and type null, for backwards compatibility)
        $criteria = array_merge($criteria, ['type' => ['$in' => [Series::TYPE_SERIES, null]]]);

        if (array_key_exists('multimedia_objects', $sorting)) {
            $repo = $this->getRepository();

            $queryBuilder = $repo->createQueryBuilder();
            $queryBuilder->setQueryArray($criteria);
            $resources = $queryBuilder->getQuery()->execute();

            $resources = $this->reorderResources($resources);

            $resources = $this->paginationService->createArrayAdapter($resources);
        } else {
            $resources = $this->createPager($criteria, $sorting);
            if (array_key_exists('textScore', $sorting)) {
                $resources->getAdapter()->getQueryBuilder()->sortMeta('score', 'textScore');
            }
        }

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

        if ($selectedSeriesId) {
            $adapter = clone $resources->getAdapter();
            $returnedSeries = $adapter->getSlice(0, $adapter->getNbResults());
            $position = 1;
            $findSerie = false;
            foreach ($returnedSeries as $series) {
                if ($selectedSeriesId == $series->getId()) {
                    $findSerie = true;

                    break;
                }
                ++$position;
            }

            $maxPerPage = $session->get($session_namespace.'/paginate', 10);
            $page = (int) (ceil($position / $maxPerPage));
            if (!$findSerie) {
                $page = 1;
            }
        } else {
            $page = $session->get($session_namespace.'/page', 1);
        }

        $resources
            ->setMaxPerPage($session->get($session_namespace.'/paginate', 10))
            ->setNormalizeOutOfRangePages(true)
            ->setCurrentPage($page)
        ;

        return $resources;
    }

    /**
     * Helper for the menu search form.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function searchAction(Request $req)
    {
        $q = $req->get('q');
        $this->get('session')->set('admin/series/criteria', ['search' => $q]);

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_index'));
    }

    /**
     * Update Broadcast Action.
     *
     * @return array|JsonResponse
     * @ParamConverter("series", class="PumukitSchemaBundle:Series", options={"id" = "id"})
     * @Template("PumukitNewAdminBundle:Series:updatebroadcast.html.twig")
     */
    public function updateBroadcastAction(Series $series, Request $request)
    {
        $mmRepo = $this->documentManager->getRepository(MultimediaObject::class);
        $broadcasts = $this->embeddedBroadcastService->getAllTypes();
        $allGroups = $this->getAllGroups();
        $embeddedBroadcast = false;
        $sameBroadcast = $this->seriesService->sameEmbeddedBroadcast($series);
        $firstFound = $this->getFirstMultimediaObject($series);

        $multimediaObjects = $mmRepo->findBySeries($series);

        if ($sameBroadcast && $firstFound) {
            $embeddedBroadcast = $firstFound->getEmbeddedBroadcast();
        }
        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            if ($request->request->has('ids')) {
                $ids = $request->get('ids');
                $multimediaObjects = $mmRepo
                    ->createStandardQueryBuilder()
                    ->field('_id')->in($ids)
                    ->getQuery()->execute();
            }

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

                foreach ($multimediaObjects as $multimediaObject) {
                    if (!$multimediaObject->isLive()) {
                        $this->modifyBroadcastGroups(
                            $multimediaObject,
                            $type,
                            $password,
                            $addGroups,
                            $deleteGroups,
                            false
                        );
                    }
                }
                $this->documentManager->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
            }
            $embeddedBroadcast = '';
            if ($firstFound) {
                $embeddedBroadcast = $firstFound->getEmbeddedBroadcast();
            }

            return new JsonResponse(['description' => (string) $embeddedBroadcast]);
        }

        return [
            'series' => $series,
            'broadcasts' => $broadcasts,
            'groups' => $allGroups,
            'sameBroadcast' => $sameBroadcast,
            'embeddedBroadcast' => $embeddedBroadcast,
            'multimediaObjects' => $mmRepo->findBySeries($series),
        ];
    }

    /**
     * List the properties of a series in a modal.
     *
     * @return array
     * @Template("PumukitNewAdminBundle:Series:listProperties.html.twig")
     */
    public function listPropertiesAction(Series $series)
    {
        return ['series' => $series];
    }

    /**
     * Returns true if the user has enough permissions to delete the $resource passed.
     * This function will always return true if the user has the MODIFY_ONWER permission. Otherwise,
     * it checks if it is the owner of the object (and there are no other owners) and returns false if not.
     * Since this is a series, that means it will check every object for ownerships.
     *
     * @return bool
     */
    protected function isUserAllowedToDelete(Series $series)
    {
        if (!$this->isGranted(Permission::MODIFY_OWNER)) {
            $loggedInUser = $this->getUser();

            $person = $this->personService->getPersonFromLoggedInUser($loggedInUser);
            $role = $this->personService->getPersonalScopeRole();
            if (!$person) {
                return false;
            }

            $enableFilter = false;
            if ($this->documentManager->getFilterCollection()->isEnabled('backoffice')) {
                $enableFilter = true;
                $this->documentManager->getFilterCollection()->disable('backoffice');
            }
            $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);
            $allMmobjs = $mmobjRepo->createStandardQueryBuilder()->field('series')->equals($series->getId())->getQuery()->execute();
            foreach ($allMmobjs as $resource) {
                if (!$resource->containsPersonWithRole($person, $role) ||
                    count($resource->getPeopleByRole($role, true)) > 1) {
                    if ($enableFilter) {
                        $this->documentManager->getFilterCollection()->enable('backoffice');
                    }

                    return false;
                }
            }
            if ($enableFilter) {
                $this->documentManager->getFilterCollection()->enable('backoffice');
            }
        }

        return true;
    }

    /**
     * Modify Multimedia Objects status.
     *
     * @param array $values
     */
    private function modifyMultimediaObjectsStatus($values)
    {
        $repo = $this->documentManager->getRepository(MultimediaObject::class);
        $repoTags = $this->documentManager->getRepository(Tag::class);

        $executeFlush = false;
        foreach ($values as $id => $value) {
            $mm = $repo->find($id);
            if ($mm) {
                if ($this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL)) {
                    foreach ($value['channels'] as $channelId => $mustContainsTag) {
                        $mustContainsTag = ('true' == $mustContainsTag);
                        $tag = $repoTags->find($channelId);
                        if ($tag && !$this->isGranted(Permission::getRoleTagDisableForPubChannel($tag->getCod()))) {
                            if ($mustContainsTag && (!($mm->containsTag($tag)))) {
                                $this->tagService->addTag($mm, $tag, false);
                                $executeFlush = true;
                            } elseif ((!($mustContainsTag)) && $mm->containsTag($tag)) {
                                $this->tagService->removeTag($mm, $tag, false);
                                $executeFlush = true;
                            }
                        }
                    }
                }

                if ($this->isGranted(Permission::CHANGE_MMOBJECT_STATUS) && $value['status'] != $mm->getStatus()) {
                    $mm->setStatus($value['status']);
                    $executeFlush = true;
                }
            }
        }

        if ($executeFlush) {
            $this->documentManager->flush();
        }
    }

    /**
     * Used in AdminController to
     * reorder series when sort is multimedia_objects.
     *
     * @param array $resources
     *
     * @return array $series
     */
    private function reorderResources($resources)
    {
        $series = [];
        foreach ($resources as $resource) {
            if (empty($series)) {
                $series[] = $resource;
            } else {
                $aux = $series;
                foreach ($aux as $index => $oneseries) {
                    if ($this->compareSeries($resource, $oneseries)) {
                        array_splice($series, $index, 0, [$resource]);

                        break;
                    }
                    if ($index == (count($aux) - 1)) {
                        $series[] = $resource;
                    }
                }
            }
        }

        return $series;
    }

    /**
     * Compare Series
     * Compare the number of multimedia objects
     * according to type (greater or lower than).
     *
     * @param Series $series1
     * @param Series $series2
     *
     * @return bool
     */
    private function compareSeries($series1, $series2)
    {
        $type = $this->get('session')->get('admin/series/type');

        $mmRepo = $this->documentManager->getRepository(MultimediaObject::class);
        $numberMultimediaObjectsInSeries1 = $mmRepo->countInSeries($series1);
        $numberMultimediaObjectsInSeries2 = $mmRepo->countInSeries($series2);

        if ('asc' === $type) {
            return $numberMultimediaObjectsInSeries1 < $numberMultimediaObjectsInSeries2;
        }
        if ('desc' === $type) {
            return $numberMultimediaObjectsInSeries1 > $numberMultimediaObjectsInSeries2;
        }

        return false;
    }

    /**
     * Modify EmbeddedBroadcast Groups.
     *
     * @param string $type
     * @param string $password
     * @param array  $addGroups
     * @param array  $deleteGroups
     * @param bool   $executeFlush
     */
    private function modifyBroadcastGroups(MultimediaObject $multimediaObject, $type = EmbeddedBroadcast::TYPE_PUBLIC, $password = '', $addGroups = [], $deleteGroups = [], $executeFlush = true)
    {
        $groupRepo = $this->documentManager->getRepository(Group::class);

        $this->embeddedBroadcastService->updateTypeAndName($type, $multimediaObject, false);
        if (EmbeddedBroadcast::TYPE_PASSWORD === $type) {
            $this->embeddedBroadcastService->updatePassword($password, $multimediaObject, false);
        } elseif (EmbeddedBroadcast::TYPE_GROUPS === $type) {
            $index = 3;
            foreach ($addGroups as $addGroup) {
                $groupId = explode('_', $addGroup)[$index];
                $group = $groupRepo->find($groupId);
                if ($group) {
                    $this->embeddedBroadcastService->addGroup($group, $multimediaObject, false);
                }
            }
            foreach ($deleteGroups as $deleteGroup) {
                $groupId = explode('_', $deleteGroup)[$index];
                $group = $groupRepo->find($groupId);
                if ($group) {
                    $this->embeddedBroadcastService->deleteGroup($group, $multimediaObject, false);
                }
            }
        }
        if ($executeFlush) {
            $this->documentManager->flush();
        }
    }

    private function getFirstMultimediaObject(Series $series)
    {
        $mmRepo = $this->documentManager->getRepository(MultimediaObject::class);
        $all = $mmRepo->findBySeries($series);

        return $all[0] ?? null;
    }
}
