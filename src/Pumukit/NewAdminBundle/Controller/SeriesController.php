<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

class SeriesController extends AdminController
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

        $update_session = true;
        foreach($resources as $series) {
            if($series->getId() == $this->get('session')->get('admin/series/id')){
                $update_session = false;
            }
        }

        if($update_session){
            $this->get('session')->remove('admin/series/id');
        }

        return array('series' => $resources);
    }

    /**
     * List action
     * @Template
     */
    public function listAction(Request $request)
    {
        $config = $this->getConfiguration();
        $session = $this->get('session');

        $sorting = $request->get('sorting', null);
        if (null !== $sorting){
            $session->set('admin/series/type', $sorting[key($sorting)]);
            $session->set('admin/series/sort', key($sorting));
        }

        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);

        return array('series' => $resources);
    }

    /**
     * Create new resource
     * @Template("PumukitNewAdminBundle:Series:list.html.twig")
     */
    public function createAction(Request $request)
    {
        $config = $this->getConfiguration();
        $pluralName = $config->getPluralResourceName();

        $series = $this->get('pumukitschema.factory');
        $series->createSeries();

        $this->addFlash('success', 'create');

        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);

        return array('series' => $resources);
    }

    /**
     * Display the form for editing or update the resource.
     */
    public function updateAction(Request $request)
    {
        $config = $this->getConfiguration();

        $resource = $this->findOr404($request);
        $this->get('session')->set('admin/series/id', $request->get('id'));
        $form = $this->getForm($resource);

        $method = $request->getMethod();
        if (in_array($method, array('POST', 'PUT', 'PATCH')) &&
            $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            $this->domainManager->update($resource);

            if ($config->isApiRequest()) {
                return $this->handleView($this->view($form));
            }

            $criteria = $this->getCriteria($config);
            $resources = $this->getResources($request, $config, $criteria);

            return $this->render('PumukitNewAdminBundle:Series:list.html.twig',
                                 array('series' => $resources)
                                 );
        }

        if ($config->isApiRequest()) {
            return $this->handleView($this->view($form));
        }

        // EDIT MULTIMEDIA OBJECT TEMPLATE CONTROLLER SOURCE CODE
        $factoryService = $this->get('pumukitschema.factory');

        $roles = $factoryService->getRoles();
        if (null === $roles){
            throw new \Exception('Not found any role.');
        }

        $parentTags = $factoryService->getParentTags();
        $mmtemplate = $factoryService->getMultimediaObjectTemplate($resource);

        $formMeta = $this->createForm('pumukitnewadmin_mmtemplate_meta', $mmtemplate);

        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $mmtemplate->getStatus()){
            $template = '_template';
        }
        // end of getting data for multimedia object template in series

        return $this->render('PumukitNewAdminBundle:Series:update.html.twig',
                             array(
                                   'series'        => $resource,
                                   'form'          => $form->createView(),
                                   'mmtemplate'    => $mmtemplate,
                                   'form_meta'     => $formMeta->createView(),
                                   'roles'         => $roles,
                                   'pub_decisions' => $pubDecisionsTags,
                                   'parent_tags'   => $parentTags,
                                   'template'      => $template
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

        $series = $this->findOr404($request);
        $seriesId = $series->getId();

        $factoryService = $this->get('pumukitschema.factory');
        $factoryService->deleteSeries($series);

        $seriesSessionId = $this->get('session')->get('admin/mms/id');
        if ($seriesId === $seriesSessionId){
            $this->get('session')->remove('admin/series/id');
        }

        $mmSessionId = $this->get('session')->get('admin/mms/id');
        if ($mmSessionId){
            $mm = $factoryService->findMultimediaObjectById($mmSessionId);
            if ($seriesId === $mm->getSeries()->getId()){
                $this->get('session')->remove('admin/mms/id');
            }
        }

        if ($config->isApiRequest()) {
            return $this->handleView($this->view());
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list', array()));
    }

    /**
     * Batch delete action
     * Overwrite to delete multimedia objects inside series
     */
    public function batchDeleteAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)){
            $ids = json_decode($ids, true);
        }

        foreach ($ids as $id) {
            $series = $this->find($id);
            $seriesId = $series->getId();

            $factoryService = $this->get('pumukitschema.factory');
            $factoryService->deleteSeries($series);

            $seriesSessionId = $this->get('session')->get('admin/mms/id');
            if ($seriesId === $seriesSessionId){
                $this->get('session')->remove('admin/series/id');
            }

            $mmSessionId = $this->get('session')->get('admin/mms/id');
            if ($mmSessionId){
                $mm = $factoryService->findMultimediaObjectById($mmSessionId);
                if ($seriesId === $mm->getSeries()->getId()){
                    $this->get('session')->remove('admin/mms/id');
                }
            }
        }
        $this->addFlash('success', 'delete');

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list', array()));
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

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        foreach ($ids as $id){
            $resource = $this->find($id);
            if ($resource->getAnnounce()){
                $resource->setAnnounce(false);
            }else{
                $resource->setAnnounce(true);
            }
            $dm->persist($resource);
        }
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list'));
    }

    /**
     * Change publication form
     * @Template("PumukitNewAdminBundle:Series:changepub.html.twig")
     */
    public function changePubAction(Request $request)
    {
        $series = $this->findOr404($request);

        $mmStatus = array(
                        'published' => MultimediaObject::STATUS_NORMAL,
                        'blocked' => MultimediaObject::STATUS_BLOQ,
                        'hidden' => MultimediaObject::STATUS_HIDE
                        );

        $pubChannels = $this->get('pumukitschema.factory')->getTagsByCod('PUBCHANNELS', true);

        return array(
                     'series' => $series,
                     'mm_status' => $mmStatus,
                     'pub_channels' => $pubChannels
                     );
    }

    /**
     * Update publication form
     */
    public function updatePubAction(Request $request)
    {
        if ('POST' === $request->getMethod()){
            $values = $request->get('values');
            if ('string' === gettype($values)){
                $values = json_decode($values, true);
            }

            $this->modifyMultimediaObjectsStatus($values);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list'));
    }

    /**
     * Gets the criteria values
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

        $new_criteria = array();
        foreach ($criteria as $property => $value) {
            //preg_match('/^\/.*?\/[imxlsu]*$/i', $e)
            if (('' !== $value) && ('title.en' === $property)) {
                $new_criteria[$property] = new \MongoRegex('/'.$value.'/i');
            } elseif (('' !== $value) && ('date' == $property)) {
                if ('' !== $value['from']) $date_from = new \DateTime($value['from']);
                if ('' !== $value['to']) $date_to = new \DateTime($value['to']);
                if (('' !== $value['from']) && ('' !== $value['to']))
                    $new_criteria['public_date'] = array('$gte' => $date_from, '$lt' => $date_to);
                elseif ('' !== $value['from'])
                    $new_criteria['public_date'] = array('$gte' => $date_from);
                elseif ('' !== $value['to'])
                    $new_criteria['public_date'] = array('$lt' => $date_to);
            } elseif (('' !== $value) && ('announce' === $property)) {
                if ('true' === $value) {
                    $new_criteria[$property] = true;
                } elseif ('false' === $value){
                    $new_criteria[$property] = false;
                }
            } elseif(('' !== $value) && ('status' === $property)) {
            } elseif(('' !== $value) && ('_id' === $property)) {
                $new_criteria['_id'] = $value;
            }
        }

        return $new_criteria;
    }


    private function getSorting($config)
    {
      $session = $this->get('session');    
      $sorting = $config->getSorting();
      if(!$sorting && $session->has('admin/series/sort')) {
            $value = $session->get('admin/series/type');
            $key = $session->get('admin/series/sort');
            $sorting = array($key => $value);
      }
      return $sorting;
    }


    /**
     * Gets the list of resources according to a criteria
     */
    public function getResources(Request $request, $config, $criteria)
    {
        $sorting = $this->getSorting($config);
        $repository = $this->getRepository();
        $session = $this->get('session');
        $session_namespace = 'admin/series';

        if ($config->isPaginated()) {
            if (array_key_exists('multimedia_objects', $sorting)){
                $resources = $this
                    ->resourceResolver
                    ->getResource($repository, 'findBy', array($criteria));
                $resources = $this->reorderResources($resources);
                $adapter = new ArrayAdapter($resources);
                $resources = new Pagerfanta($adapter);
            }else{
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

            $resources
                ->setCurrentPage($session->get($session_namespace.'/page', 1), true, true)
                ->setMaxPerPage($session->get($session_namespace.'/paginate', 10));
        } else {
            $resources = $this
                ->resourceResolver
                ->getResource($repository, 'findBy', array($criteria, $sorting, $config->getLimit()));
        }

        return $resources;
    }

    /**
     * Modify Multimedia Objects status
     */
    private function modifyMultimediaObjectsStatus($values)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $repoTags = $dm->getRepository('PumukitSchemaBundle:Tag');
        $tagService = $this->get('pumukitschema.tag');

        foreach ($values as $id => $value){
            $mm = $repo->find($id);
            if ($mm){
                foreach($value['channels'] as $channelId => $mustContainsTag){
                    $tag = $repoTags->find($channelId);
                    if ($mustContainsTag && (!($mm->containsTag($tag)))) {
                        $tagAdded = $tagService->addTagToMultimediaObject($mm, $tag->getId());
                    }elseif ((!($mustContainsTag)) && $mm->containsTag($tag)) {
                        $tagAdded = $tagService->removeTagFromMultimediaObject($mm, $tag->getId());
                    }
                }
                $mm->setStatus($value['status']);
                $dm->persist($mm);
            }
        }
        $dm->flush();
    }

    /**
     * Used in AdminController to
     * reorder series when sort is multimedia_objects
     *
     * @param ArrayCollection $resources
     * @param string $type 'asc'|'desc'
     * @return array $series
     */
    private function reorderResources($resources)
    {
        $series = array();
        foreach($resources as $resource){
            if (empty($series)) {
                $series[] = $resource;
            }else{
                $aux = $series;
                foreach($aux as $index => $oneseries){
                    if ($this->compareSeries($resource, $oneseries)){
                        array_splice($series, $index, 0, array($resource));
                        break;
                    }elseif ($index == (count($aux) - 1)){
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
     * according to type (greater or lower than)
     *
     * @param Series $series1
     * @param Series $series2
     * @return boolean
     */
    private function compareSeries($series1, $series2)
    {
        $type = $this->get('session')->get('admin/series/type');

        if ('asc' === $type){
            return (count($series1->getMultimediaObjects()) < count($series2->getMultimediaObjects()));
        }elseif('desc' === $type){
            return (count($series1->getMultimediaObjects()) > count($series2->getMultimediaObjects()));
        }

        return false;
    }
}
