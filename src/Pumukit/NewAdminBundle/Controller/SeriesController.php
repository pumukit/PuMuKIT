<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\NewAdminBundle\Form\Type\SeriesType;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectTemplateMetaType;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\Group;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class SeriesController extends AdminController implements NewAdminControllerInterface
{
    public static $resourceName = 'series';
    public static $repoName = Series::class;

    /**
     * Overwrite to search criteria with date.
     *
     * @param Request $request
     *
     * @return array
     * @Template
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
     * @param Request $request
     *
     * @return array
     * @Template
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
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $factory = $this->get('pumukitschema.factory');
        $series = $factory->createSeries($this->getUser());
        $this->get('session')->set('admin/series/id', $series->getId());

        return new JsonResponse(['seriesId' => $series->getId()]);
    }

    /**
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Exception
     */
    public function cloneAction($id)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $translator = $this->get('translator');

        $series = $dm->getRepository(Series::class)->findOneBy(['_id' => new \MongoId($id)]);
        if (!$series) {
            throw new \Exception($translator->trans('No series found with ID').' '.$id);
        }

        $factoryService = $this->get('pumukitschema.factory');

        try {
            $factoryService->cloneSeries($series);

            return $this->redirectToRoute('pumukitnewadmin_series_list');
        } catch (\Exception $exception) {
            throw new \Exception($translator->trans('Error while cloning series ').$exception->getMessage());
        }
    }

    /**
     * @param Series $resource
     *
     * @return array
     * @Template
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
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function updateAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $this->get('session')->set('admin/series/id', $request->get('id'));

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $disablePudenew = !$this->container->getParameter('show_latest_with_pudenew');
        $form = $this->createForm(SeriesType::class, $resource, ['translator' => $translator, 'locale' => $locale, 'disable_PUDENEW' => $disablePudenew]);

        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH']) &&
            $form->handleRequest($request)->isValid()) {
            $this->update($resource);
            $this->get('pumukitschema.series_dispatcher')->dispatchUpdate($resource);
            if (Series::SORT_MANUAL !== $resource->getSorting()) {
                $this->get('pumukitschema.sorted_multimedia_object')->reorder($resource);
            }

            $criteria = $this->getCriteria($request->get('criteria', []));
            $resources = $this->getResources($request, $criteria, $resource->getId());

            return $this->render(
                'PumukitNewAdminBundle:Series:list.html.twig',
                ['series' => $resources]
            );
        }

        // EDIT MULTIMEDIA OBJECT TEMPLATE CONTROLLER SOURCE CODE
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
        $mmtemplate = $factoryService->getMultimediaObjectPrototype($resource);

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $formMeta = $this->createForm(MultimediaObjectTemplateMetaType::class, $mmtemplate, ['translator' => $translator, 'locale' => $locale]);

        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

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
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function deleteAction(Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');

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
            $mm = $factoryService->findMultimediaObjectById($mmSessionId);
            if ($seriesId === $mm->getSeries()->getId()) {
                $this->get('session')->remove('admin/mms/id');
            }
        }

        try {
            $factoryService->deleteSeries($series);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->redirectToRoute('pumukitnewadmin_series_list');
    }

    /**
     * Generate Magic Url action.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function generateMagicUrlAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $mmobjService = $this->get('pumukitschema.series');
        $response = $mmobjService->resetMagicUrl($resource);

        return new Response($response);
    }

    /**
     * Batch delete action
     * Overwrite to delete multimedia objects inside series.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function batchDeleteAction(Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');

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
                $mm = $factoryService->findMultimediaObjectById($mmSessionId);
                if ($seriesId === $mm->getSeries()->getId()) {
                    $this->get('session')->remove('admin/mms/id');
                }
            }

            try {
                $factoryService->deleteSeries($series);
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
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function invertAnnounceAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
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
            $dm->persist($resource);
        }
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list'));
    }

    /**
     * Change publication form.
     *
     * @param Request $request
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

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $multimediaObjects = $dm->getRepository(MultimediaObject::class)->findWithoutPrototype($series);

        $pubChannels = $this->get('pumukitschema.factory')->getTagsByCod('PUBCHANNELS', true);

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
     * @param Request $request
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

    /**
     * Gets the criteria values.
     *
     * @param $criteria
     *
     * @return array
     */
    public function getCriteria($criteria)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $criteria = $request->get('criteria', []);

        $emptySeries = [];
        if ($request->query->has('empty_series') || $this->get('session')->has('admin/series/empty_series')) {
            $this->get('session')->set('admin/series/empty_series', true);
            $dm = $this->get('doctrine_mongodb')->getManager();
            $mmObjColl = $dm->getDocumentCollection(MultimediaObject::class);
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

        $new_criteria = $this->get('pumukitnewadmin.series_search')->processCriteria($criteria, false, $request->getLocale());

        return $new_criteria;
    }

    /**
     * @param Request $request
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
     * @param Request       $request
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
            $adapter = new ArrayAdapter($resources);
            $resources = new Pagerfanta($adapter);
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
            ->setCurrentPage($page);

        return $resources;
    }

    /**
     * Modify Multimedia Objects status.
     *
     * @param $values
     */
    private function modifyMultimediaObjectsStatus($values)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository(MultimediaObject::class);
        $repoTags = $dm->getRepository(Tag::class);
        $tagService = $this->get('pumukitschema.tag');

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
                                $tagService->addTag($mm, $tag, false);
                                $executeFlush = true;
                            } elseif ((!($mustContainsTag)) && $mm->containsTag($tag)) {
                                $tagService->removeTag($mm, $tag, false);
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
            $dm->flush();
        }
    }

    /**
     * Used in AdminController to
     * reorder series when sort is multimedia_objects.
     *
     * @param $resources
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
                    } elseif ($index == (count($aux) - 1)) {
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

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmRepo = $dm->getRepository(MultimediaObject::class);
        $numberMultimediaObjectsInSeries1 = $mmRepo->countInSeries($series1);
        $numberMultimediaObjectsInSeries2 = $mmRepo->countInSeries($series2);

        if ('asc' === $type) {
            return $numberMultimediaObjectsInSeries1 < $numberMultimediaObjectsInSeries2;
        } elseif ('desc' === $type) {
            return $numberMultimediaObjectsInSeries1 > $numberMultimediaObjectsInSeries2;
        }

        return false;
    }

    /**
     * Helper for the menu search form.
     *
     * @param Request $req
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
     * Returns true if the user has enough permissions to delete the $resource passed.
     * This function will always return true if the user has the MODIFY_ONWER permission. Otherwise,
     * it checks if it is the owner of the object (and there are no other owners) and returns false if not.
     * Since this is a series, that means it will check every object for ownerships.
     *
     * @param Series $series
     *
     * @return bool
     */
    protected function isUserAllowedToDelete(Series $series)
    {
        if (!$this->isGranted(Permission::MODIFY_OWNER)) {
            $loggedInUser = $this->getUser();
            $personService = $this->get('pumukitschema.person');
            $person = $personService->getPersonFromLoggedInUser($loggedInUser);
            $role = $personService->getPersonalScopeRole();
            if (!$person) {
                return false;
            }
            $dm = $this->get('doctrine_mongodb.odm.document_manager');

            $enableFilter = false;
            if ($dm->getFilterCollection()->isEnabled('backoffice')) {
                $enableFilter = true;
                $dm->getFilterCollection()->disable('backoffice');
            }
            $mmobjRepo = $dm->getRepository(MultimediaObject::class);
            $allMmobjs = $mmobjRepo->createStandardQueryBuilder()->field('series')->equals($series->getId())->getQuery()->execute();
            foreach ($allMmobjs as $resource) {
                if (!$resource->containsPersonWithRole($person, $role) ||
                    count($resource->getPeopleByRole($role, true)) > 1) {
                    if ($enableFilter) {
                        $dm->getFilterCollection()->enable('backoffice');
                    }

                    return false;
                }
            }
            if ($enableFilter) {
                $dm->getFilterCollection()->enable('backoffice');
            }
        }

        return true;
    }

    /**
     * Update Broadcast Action.
     *
     * @param Series  $series
     * @param Request $request
     *
     * @return array|JsonResponse
     * @ParamConverter("series", class="PumukitSchemaBundle:Series", options={"id" = "id"})
     * @Template("PumukitNewAdminBundle:Series:updatebroadcast.html.twig")
     */
    public function updateBroadcastAction(Series $series, Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmRepo = $dm->getRepository(MultimediaObject::class);
        $broadcasts = $this->get('pumukitschema.embeddedbroadcast')->getAllTypes();
        $allGroups = $this->getAllGroups();
        $seriesService = $this->get('pumukitschema.series');
        $embeddedBroadcast = false;
        $sameBroadcast = $seriesService->sameEmbeddedBroadcast($series);
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
                $dm->flush();
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
     * @param Series $series
     *
     * @return array
     * @Template
     */
    public function listPropertiesAction(Series $series)
    {
        return ['series' => $series];
    }

    /**
     * Modify EmbeddedBroadcast Groups.
     *
     * @param MultimediaObject $multimediaObject
     * @param string           $type
     * @param string           $password
     * @param array            $addGroups
     * @param array            $deleteGroups
     * @param bool             $executeFlush
     */
    private function modifyBroadcastGroups(MultimediaObject $multimediaObject, $type = EmbeddedBroadcast::TYPE_PUBLIC, $password = '', $addGroups = [], $deleteGroups = [], $executeFlush = true)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $groupRepo = $dm->getRepository(Group::class);
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $embeddedBroadcastService->updateTypeAndName($type, $multimediaObject, false);
        if (EmbeddedBroadcast::TYPE_PASSWORD === $type) {
            $embeddedBroadcastService->updatePassword($password, $multimediaObject, false);
        } elseif (EmbeddedBroadcast::TYPE_GROUPS === $type) {
            $index = 3;
            foreach ($addGroups as $addGroup) {
                $groupId = explode('_', $addGroup)[$index];
                $group = $groupRepo->find($groupId);
                if ($group) {
                    $embeddedBroadcastService->addGroup($group, $multimediaObject, false);
                }
            }
            foreach ($deleteGroups as $deleteGroup) {
                $groupId = explode('_', $deleteGroup)[$index];
                $group = $groupRepo->find($groupId);
                if ($group) {
                    $embeddedBroadcastService->deleteGroup($group, $multimediaObject, false);
                }
            }
        }
        if ($executeFlush) {
            $dm->flush();
        }
    }

    /**
     * @param Series $series
     */
    private function getFirstMultimediaObject(Series $series)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmRepo = $dm->getRepository(MultimediaObject::class);
        $all = $mmRepo->findBySeries($series);
        foreach ($all as $multimediaObject) {
            return $multimediaObject;
        }

        return null;
    }
}
