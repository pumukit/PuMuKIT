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

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class SeriesController extends AdminController implements NewAdminController
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

        $update_session = true;
        foreach ($resources as $series) {
            if ($series->getId() == $this->get('session')->get('admin/series/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $this->get('session')->remove('admin/series/id');
        }

        return array(
            'series' => $resources,
            'disable_pudenew' => !$this->container->getParameter('show_latest_with_pudenew'),
        );
    }

    /**
     * List action.
     *
     * @Template
     */
    public function listAction(Request $request)
    {
        $config = $this->getConfiguration();
        $criteria = $this->getCriteria($config);
        $selectedSeriesId = $request->get('selectedSeriesId', null);
        $resources = $this->getResources($request, $config, $criteria, $selectedSeriesId);

        return array('series' => $resources);
    }

    /**
     * Create new resource.
     */
    public function createAction(Request $request)
    {
        $factory = $this->get('pumukitschema.factory');
        $series = $factory->createSeries($this->getUser());
        $this->get('session')->set('admin/series/id', $series->getId());

        return new JsonResponse(array('seriesId' => $series->getId()));
    }

    /**
     * Display the form for editing or update the resource.
     */
    public function updateAction(Request $request)
    {
        $config = $this->getConfiguration();

        $resource = $this->findOr404($request);
        $this->get('session')->set('admin/series/id', $request->get('id'));

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $disablePudenew = !$this->container->getParameter('show_latest_with_pudenew');
        $form = $this->createForm(new SeriesType($translator, $locale, $disablePudenew), $resource);

        $method = $request->getMethod();
        if (in_array($method, array('POST', 'PUT', 'PATCH')) &&
            $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            $this->domainManager->update($resource);
            $this->get('pumukitschema.series_dispatcher')->dispatchUpdate($resource);

            if ($config->isApiRequest()) {
                return $this->handleView($this->view($form));
            }

            $criteria = $this->getCriteria($config);
            $resources = $this->getResources($request, $config, $criteria, $resource->getId());

            return $this->render('PumukitNewAdminBundle:Series:list.html.twig',
                                 array('series' => $resources)
                                 );
        }

        if ($config->isApiRequest()) {
            return $this->handleView($this->view($form));
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

        $formMeta = $this->createForm(new MultimediaObjectTemplateMetaType($translator, $locale), $mmtemplate);

        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        //These fields are form fields that are rendered separately, so they should be 'excluded' from the generic foreach.
        //FIXME: There is a cleaner approach FOR SURE.
        $exclude_fields = array();
        $show_later_fields = array('pumukitnewadmin_series_i18n_header', 'pumukitnewadmin_series_i18n_footer', 'pumukitnewadmin_series_i18n_line2', 'pumukitnewadmin_series_template');
        $showSeriesTypeTab = $this->container->hasParameter('pumukit2.use_series_channels') && $this->container->getParameter('pumukit2.use_series_channels');
        if (!$showSeriesTypeTab) {
            $exclude_fields[] = 'pumukitnewadmin_series_series_type';
        }

        return $this->render('PumukitNewAdminBundle:Series:update.html.twig',
                             array(
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
                                   )
                             );
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function deleteAction(Request $request)
    {
        $config = $this->getConfiguration();
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

        if ($config->isApiRequest()) {
            return $this->handleView($this->view());
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list', array()));
    }

    /**
     * Generate Magic Url action.
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
     */
    public function batchDeleteAction(Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');

        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        foreach ($ids as $id) {
            $series = $this->find($id);
            if (!$this->isUserAllowedToDelete($series)) {
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
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list', array()));
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

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        foreach ($ids as $id) {
            $resource = $this->find($id);
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
     * @Template("PumukitNewAdminBundle:Series:changepub.html.twig")
     */
    public function changePubAction(Request $request)
    {
        $series = $this->findOr404($request);

        $mmStatus = array(
                        'published' => MultimediaObject::STATUS_PUBLISHED,
                        'blocked' => MultimediaObject::STATUS_BLOQ,
                        'hidden' => MultimediaObject::STATUS_HIDE,
                        );

        $pubChannels = $this->get('pumukitschema.factory')->getTagsByCod('PUBCHANNELS', true);

        return array(
                     'series' => $series,
                     'mm_status' => $mmStatus,
                     'pub_channels' => $pubChannels,
                     );
    }

    /**
     * Update publication form.
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
     */
    public function getCriteria($config)
    {
        $criteria = $this->getRequest()->get('criteria', array());

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/series/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/series/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/series/criteria', array());

        $new_criteria = $this->get('pumukitnewadmin.series_search')->processCriteria($criteria, true);

        return $new_criteria;
    }

    private function getSorting(Request $request)
    {
        $session = $this->get('session');

        if ($sorting = $request->get('sorting')) {
            $session->set('admin/series/type', current($sorting));
            $session->set('admin/series/sort', key($sorting));
        }

        $value = $session->get('admin/series/type', 'desc');
        $key = $session->get('admin/series/sort', 'public_date');

        if ($key == 'title') {
            $key .= '.'.$request->getLocale();
        }

        return  array($key => $value);
    }

    /**
     * Gets the list of resources according to a criteria.
     */
    public function getResources(Request $request, $config, $criteria, $selectedSeriesId = null)
    {
        $sorting = $this->getSorting($request);
        $repository = $this->getRepository();
        $session = $this->get('session');
        $session_namespace = 'admin/series';
        //Added TYPE_SERIES to criteria (and type null, for backwards compatibility)
        $criteria = array_merge($criteria, array('type' => array('$in' => array(Series::TYPE_SERIES, null))));

        if ($config->isPaginated()) {
            if (array_key_exists('multimedia_objects', $sorting)) {
                $resources = $this
                    ->resourceResolver
                    ->getResource($repository, 'findBy', array($criteria));
                $resources = $this->reorderResources($resources);
                $adapter = new ArrayAdapter($resources);
                $resources = new Pagerfanta($adapter);
            } else {
                $resources = $this
                    ->resourceResolver
                    ->getResource($repository, 'createPaginator', array($criteria, $sorting));
            }

            if ($request->get('page', null)) {
                $session->set($session_namespace.'/page', $request->get('page', 1));
            }

            if ($request->get('paginate', null)) {
                $session->set($session_namespace.'/paginate', $request->get('paginate', 10));
            }

            if ($selectedSeriesId) {
                $newSeries = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:Series')->find($selectedSeriesId);
                $adapter = $resources->getAdapter();
                $returnedSeries = $adapter->getSlice(0, $adapter->getNbResults());
                $position = 1;
                foreach ($returnedSeries as $series) {
                    if ($selectedSeriesId == $series->getId()) {
                        break;
                    }
                    ++$position;
                }
                $maxPerPage = $session->get($session_namespace.'/paginate', 10);
                $page = intval(ceil($position / $maxPerPage));
            } else {
                $page = $session->get($session_namespace.'/page', 1);
            }
            $resources
                ->setMaxPerPage($session->get($session_namespace.'/paginate', 10))
                ->setNormalizeOutOfRangePages(true)
                ->setCurrentPage($page);
        } else {
            $resources = $this
                ->resourceResolver
                ->getResource($repository, 'findBy', array($criteria, $sorting, $config->getLimit()));
        }

        return $resources;
    }

    /**
     * Modify Multimedia Objects status.
     */
    private function modifyMultimediaObjectsStatus($values)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $repoTags = $dm->getRepository('PumukitSchemaBundle:Tag');
        $tagService = $this->get('pumukitschema.tag');

        foreach ($values as $id => $value) {
            $mm = $repo->find($id);
            if ($mm) {
                if ($this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL)) {
                    foreach ($value['channels'] as $channelId => $mustContainsTag) {
                        $mustContainsTag = ('true' == $mustContainsTag);
                        $dm->clear(); //See #12692
                        $tag = $repoTags->find($channelId);
                        $mm = $repo->find($id);
                        if (!$this->isGranted(Permission::getRoleTagDisableForPubChannel($tag->getCod()))) {
                            if ($mustContainsTag && (!($mm->containsTag($tag)))) {
                                $tagAdded = $tagService->addTag($mm, $tag);
                            } elseif ((!($mustContainsTag)) && $mm->containsTag($tag)) {
                                $tagAdded = $tagService->removeTag($mm, $tag);
                            }
                        }
                    }
                }

                if (
                    $this->isGranted(Permission::CHANGE_MMOBJECT_STATUS) &&
                    $value['status'] != $mm->getStatus()
                ) {
                    $mm->setStatus($value['status']);
                    $dm->persist($mm);
                    $dm->flush();
                }
            }
        }
    }

    /**
     * Used in AdminController to
     * reorder series when sort is multimedia_objects.
     *
     * @param ArrayCollection $resources
     * @param string          $type      'asc'|'desc'
     *
     * @return array $series
     */
    private function reorderResources($resources)
    {
        $series = array();
        foreach ($resources as $resource) {
            if (empty($series)) {
                $series[] = $resource;
            } else {
                $aux = $series;
                foreach ($aux as $index => $oneseries) {
                    if ($this->compareSeries($resource, $oneseries)) {
                        array_splice($series, $index, 0, array($resource));
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
        $mmRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
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
     */
    public function searchAction(Request $req)
    {
        $q = $req->get('q');
        $this->get('session')->set('admin/series/criteria', array('search' => $q));

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_index'));
    }

    /**
     * Returns true if the user has enough permissions to delete the $resource passed.
     *
     * This function will always return true if the user has the MODIFY_ONWER permission. Otherwise,
     * it checks if it is the owner of the object (and there are no other owners) and returns false if not.
     * Since this is a series, that means it will check every object for ownerships.
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
            $filter = $dm->getFilterCollection()->disable('backoffice');
            $mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
            $allMmobjs = $mmobjRepo->createStandardQueryBuilder()->field('series')->equals($series->getId())->getQuery()->execute();
            foreach ($allMmobjs as $resource) {
                if (!$resource->containsPersonWithRole($person, $role) ||
                   count($resource->getPeopleByRole($role, true)) > 1) {
                    $filter = $dm->getFilterCollection()->enable('backoffice');

                    return false;
                }
            }
            $filter = $dm->getFilterCollection()->enable('backoffice');
        }

        return true;
    }

    /**
     * Update Broadcast Action.
     *
     * @ParamConverter("series", class="PumukitSchemaBundle:Series", options={"id" = "id"})
     * @Template("PumukitNewAdminBundle:Series:updatebroadcast.html.twig")
     */
    public function updateBroadcastAction(Series $series, Request $request)
    {
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $broadcasts = $this->get('pumukitschema.embeddedbroadcast')->getAllTypes();
        $allGroups = $this->getAllGroups();
        $seriesService = $this->get('pumukitschema.series');
        $embeddedBroadcast = false;
        $sameBroadcast = $seriesService->sameEmbeddedBroadcast($series);
        $firstFound = $this->getFirstMultimediaObject($series);
        if ($sameBroadcast && $firstFound) {
            $embeddedBroadcast = $firstFound->getEmbeddedBroadcast();
        }
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
                $multimediaObjects = $mmRepo->findBySeries($series);
                foreach ($multimediaObjects as $multimediaObject) {
                    $this->modifyBroadcastGroups($multimediaObject, $type, $password, $addGroups, $deleteGroups, false);
                }
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('error' => $e->getMessage()), JsonResponse::HTTP_BAD_REQUEST);
            }
            $embeddedBroadcast = '';
            if ($firstFound) {
                $embeddedBroadcast = $firstFound->getEmbeddedBroadcast();
            }

            return new JsonResponse(array('description' => (string) $embeddedBroadcast));
        }

        return array(
                     'series' => $series,
                     'broadcasts' => $broadcasts,
                     'groups' => $allGroups,
                     'sameBroadcast' => $sameBroadcast,
                     'embeddedBroadcast' => $embeddedBroadcast,
                     );
    }

    /**
     * List the properties of a series in a modal.
     *
     * @Template
     */
    public function listPropertiesAction(Series $series)
    {
        return array('series' => $series);
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
    private function modifyBroadcastGroups(MultimediaObject $multimediaObject, $type = EmbeddedBroadcast::TYPE_PUBLIC, $password = '', $addGroups = array(), $deleteGroups = array(), $executeFlush = true)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $groupRepo = $dm->getRepository('PumukitSchemaBundle:Group');
        $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $embeddedBroadcastService->updateTypeAndName($type, $multimediaObject, false);
        if ($type === EmbeddedBroadcast::TYPE_PASSWORD) {
            $embeddedBroadcastService->updatePassword($password, $multimediaObject, false);
        } elseif ($type === EmbeddedBroadcast::TYPE_GROUPS) {
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

    private function getFirstMultimediaObject(Series $series)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $all = $mmRepo->findBySeries($series);
        foreach ($all as $multimediaObject) {
            return $multimediaObject;
        }

        return null;
    }
}
