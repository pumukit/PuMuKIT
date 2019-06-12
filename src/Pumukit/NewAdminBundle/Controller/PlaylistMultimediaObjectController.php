<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PlaylistMultimediaObjectController extends Controller
{
    /**
     * Overwrite to search criteria with date.
     *
     * @Template
     */
    public function indexAction(Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $session = $this->get('session');
        $sessionId = $session->get('admin/playlist/id', null);
        $series = $factoryService->findSeriesById($request->query->get('id'), $sessionId);
        if (!$series) {
            throw $this->createNotFoundException();
        }

        $session->set('admin/playlist/id', $series->getId());
        if ($request->query->has('mmid')) {
            $session->set('admin/playlistmms/id', $request->query->get('mmid'));
        }

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $mms = $this->getPlaylistMmobjs($series, $request);

        // Removes the session mmobj (shown on info and preview) if it does not belong to THIS playlist.
        $update_session = true;
        foreach ($mms as $mm) {
            if ($mm->getId() == $this->get('session')->get('admin/playlistmms/id')) {
                $update_session = false;
                break;
            }
        }
        if ($update_session) {
            $session->remove('admin/playlistmms/id');
        }

        return array(
            'playlist' => $series,
            'mms' => $mms,
        );
    }

    /**
     * Displays the preview.
     *
     * @Template("PumukitNewAdminBundle:MultimediaObject:show.html.twig")
     */
    public function showAction(MultimediaObject $mmobj, Request $request)
    {
        $this->get('session')->set('admin/playlistmms/id', $mmobj->getId());
        if ($request->query->has('pos')) {
            $this->get('session')->set('admin/playlistmms/pos', $request->query->get('pos'));
        }
        $roles = $this->get('pumukitschema.person')->getRoles();
        $activeEditor = $this->checkHasEditor();

        return array(
            'mm' => $mmobj,
            'roles' => $roles,
            'active_editor' => $activeEditor,
        );
    }

    /**
     * Displays the 'info' tab.
     *
     * @Template
     */
    public function infoAction(MultimediaObject $mmobj, Request $request)
    {
        $mmService = $this->get('pumukitschema.multimedia_object');
        $warningOnUnpublished = $this->container->getParameter('pumukit2.warning_on_unpublished');

        return array(
            'mm' => $mmobj,
            'is_published' => $mmService->isPublished($mmobj, 'PUCHWEBTV'),
            'is_hidden' => $mmService->isHidden($mmobj, 'PUCHWEBTV'),
            'is_playable' => $mmService->hasPlayableResource($mmobj),
            'warning_on_unpublished' => $warningOnUnpublished,
        );
    }

    /**
     * @Template
     */
    public function listAction(Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $sessionId = $this->get('session')->get('admin/playlist/id', null);
        $series = $factoryService->findSeriesById($request->query->get('id'), $sessionId);
        if (!$series) {
            throw $this->createNotFoundException();
        }

        $this->get('session')->set('admin/playlist/id', $series->getId());

        if ($request->query->has('mmid')) {
            $this->get('session')->set('admin/playlistmms/id', $request->query->get('mmid'));
        }

        $mms = $this->getPlaylistMmobjs($series, $request);

        return array(
            'playlist' => $series,
            'mms' => $mms,
        );
    }

    //TODO: This? Or getResources(like in PlaylistController?)
    protected function getPlaylistMmobjs(Series $series, $request)
    {
        $mmsList = $series->getPlaylist()->getMultimediaObjects();
        $adapter = new DoctrineCollectionAdapter($mmsList);
        $pagerfanta = new Pagerfanta($adapter);

        $session = $this->get('session');
        if ($request->get('page', null)) {
            $session->set('admin/playlistmms/page', $request->get('page', 1));
        }

        if ($request->get('paginate', null)) {
            $session->set('admin/playlistmms/paginate', $request->get('paginate', 10));
        }

        $page = $session->get('admin/playlistmms/page', 1);
        $maxPerPage = $session->get('admin/playlistmms/paginate', 10);

        $pagerfanta->setMaxPerPage($maxPerPage)->setNormalizeOutOfRangePages(true);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    /**
     * Returns a modal window where to add mmobjs to a playlist.
     *
     * It is meant to be used through ajax.
     *
     * @Template("PumukitNewAdminBundle:PlaylistMultimediaObject:modal.html.twig")
     */
    public function modalAction(Series $playlist, Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $limit = $request->get('modal_limit', 20);
        //Get all multimedia objects. The filter will do the rest.
        $mmobjs = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->createStandardQueryBuilder();
        $total = $mmobjs->count()->getQuery()->execute();

        return array(
            'my_mmobjs' => array(),
            'mmobjs_total' => $total,
            'mmobjs_limit' => $limit,
            'playlist' => $playlist,
        );
    }

    /**
     * Returns the user mmobjs.
     *
     * It is meant to be used through ajax.
     *
     * @Template("PumukitNewAdminBundle:PlaylistMultimediaObject:modal_myvideos_list.html.twig")
     */
    public function modalMyMmobjsAction(Series $playlist, Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $page = $request->get('modal_page', 1);
        $limit = $request->get('modal_limit', 20);
        //Get all multimedia objects. The filter will do the rest.
        $mmobjs = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->createStandardQueryBuilder();
        $adapter = new DoctrineODMMongoDBAdapter($mmobjs);
        $mmobjs = new Pagerfanta($adapter);
        $mmobjs
          ->setMaxPerPage($limit)
          ->setNormalizeOutOfRangePages(true);

        $mmobjs->setCurrentPage($page);

        return array(
            'my_mmobjs' => $mmobjs,
        );
    }

    /**
     * @Template("PumukitNewAdminBundle:PlaylistMultimediaObject:modal_search_list.html.twig")
     */
    public function searchModalAction(Request $request)
    {
        $this->enableFilter();
        $limit = 50;
        $value = $request->query->get('search', '');

        $criteria = array('search' => $value);
        $criteria = $this->get('pumukitnewadmin.multimedia_object_search')->processMMOCriteria($criteria, $request->getLocale());

        $queryBuilder = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:MultimediaObject')->createStandardQueryBuilder();
        $criteria = array_merge($queryBuilder->getQueryArray(), $criteria);
        $queryBuilder->setQueryArray($criteria);
        $queryBuilder->limit($limit);
        $queryBuilder->sortMeta('score', 'textScore');

        $adapter = new DoctrineODMMongoDBAdapter($queryBuilder);
        $mmobjs = new Pagerfanta($adapter);
        $mmobjs->setMaxPerPage($limit);

        return array('mmobjs' => $mmobjs);
    }

    /**
     * @Template("PumukitNewAdminBundle:PlaylistMultimediaObject:modal_url_list.html.twig")
     */
    public function urlModalAction(Request $request)
    {
        $broadcastService = $this->get('pumukitschema.embeddedbroadcast');
        $mmobjService = $this->get('pumukitschema.multimedia_object');
        $this->enableFilter();
        $id = $request->query->get('mmid', '');
        $mmobj = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:MultimediaObject')->find($id);
        $user = $this->getUser();
        if ($mmobj) {
            $canBePlayed = $broadcastService->canUserPlayMultimediaObject($mmobj, $user);
            $canUserPlay = $mmobjService->canBeDisplayed($mmobj, 'PUCHWEBTV');
        }
        if ($mmobj && (!$canBePlayed || !$canUserPlay)) {
            $mmobj = null;
        }

        return array(
            'mmobj' => $mmobj,
            'mmobj_id' => $id,
        );
    }

    public function addBatchAction(Series $playlist, Request $request)
    {
        $this->enableFilter();
        $mmobjIds = $request->query->get('ids', '');
        if (!$mmobjIds) {
            throw $this->createNotFoundException();
        }

        if ('string' === gettype($mmobjIds)) {
            $mmobjIds = json_decode($mmobjIds, true);
        }

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        foreach ($mmobjIds as $id) {
            $mmobj = $mmobjRepo->find($id);
            if (!$mmobj) {
                continue;
            }
            $playlist->getPlaylist()->addMultimediaObject($mmobj);
            $dm->persist($playlist);
        }
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlistmms_list', array('id' => $playlist->getId())));
    }

    public function deleteBatchAction(Series $playlist, Request $request)
    {
        $mmobjIds = $request->query->get('ids', '');
        if (!$mmobjIds) {
            throw $this->createNotFoundException();
        }

        if ('string' === gettype($mmobjIds)) {
            $mmobjIds = json_decode($mmobjIds, true);
        }

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        //$mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $mms = $playlist->getPlaylist()->getMultimediaObjects();
        foreach ($mmobjIds as $pos => $id) {
            if (isset($mms[$pos]) && $mms[$pos]->getId() == $id) {
                $playlist->getPlaylist()->removeMultimediaObjectByPos($pos);
            }
        }
        $dm->persist($playlist);
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlistmms_list', array('id' => $playlist->getId())));
    }

    /**
     * Adds mmobj to the playlist. (as last).
     */
    public function addMmobjAction(Series $series, Request $request)
    {
        $this->enableFilter();
        if (!$request->query->has('mm_id')) {
            throw new \Exception('The request is missing the \'mm_id\' parameter');
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');

        $playlistEmbed = $series->getPlaylist();
        $mmobjId = $request->query->get('mm_id');
        $mm = $mmobjRepo->find($mmobjId);
        if (!$mm) {
            throw new \Exception("The id: $mmobjId , does not belong to any Multimedia Object");
        }

        $playlistEmbed->addMultimediaObject($mm);
        $dm->persist($playlistEmbed);
        $dm->flush();
    }

    /**
     * Moves the mmobj in $initPos to $endPos.
     */
    protected function moveAction(Series $playlist, $initPos, $endPos)
    {
        $actionResponse = $this->redirect($this->generateUrl('pumukitnewadmin_playlistmms_index', array('id' => $playlist->getId())));
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $playlist->getPlaylist()->moveMultimediaObject($initPos, $endPos);
        $dm->persist($playlist);
        $dm->flush();

        return $actionResponse;
    }

    public function upAction(Series $playlist, Request $request)
    {
        $initPos = $request->query->get('mm_pos');
        $endPos = ($initPos < 1) ? 0 : $initPos - 1;

        return $this->moveAction($playlist, $initPos, $endPos);
    }

    public function downAction(Series $playlist, Request $request)
    {
        $initPos = $request->query->get('mm_pos');
        $numMmobjs = count($playlist->getPlaylist()->getMultimediaObjects());
        $lastPos = $numMmobjs - 1;
        $endPos = ($initPos >= $lastPos) ? $lastPos : $initPos + 1;

        return $this->moveAction($playlist, $initPos, $endPos);
    }

    public function topAction(Series $playlist, Request $request)
    {
        $initPos = $request->query->get('mm_pos');
        $firstPos = 0;

        return $this->moveAction($playlist, $initPos, $firstPos);
    }

    public function bottomAction(Series $playlist, Request $request)
    {
        $initPos = $request->query->get('mm_pos');
        $lastPos = -1;

        return $this->moveAction($playlist, $initPos, $lastPos);
    }

    //Workaround function to check if the VideoEditorBundle is installed.
    protected function checkHasEditor()
    {
        $router = $this->get('router');
        $routes = $router->getRouteCollection()->all();
        $activeEditor = array_key_exists('pumukit_videoeditor_index', $routes);

        return $activeEditor;
    }

    //Disables the standard backoffice filter and enables the 'personal' filter. (Check own videos or public videos)
    protected function enableFilter()
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $user = $this->get('security.context')->getToken()->getUser();
        if ($this->isGranted(PermissionProfile::SCOPE_GLOBAL)) {
            return;
        }
        $dm->getFilterCollection()->disable('backoffice');
        $filter = $dm->getFilterCollection()->enable('personal');
        $person = $this->get('pumukitschema.person')->getPersonFromLoggedInUser($user);
        $people = array();
        if ((null !== $person) && (null !== ($roleCode = $this->get('pumukitschema.person')->getPersonalScopeRoleCode()))) {
            $people['$elemMatch'] = array();
            $people['$elemMatch']['people._id'] = new \MongoId($person->getId());
            $people['$elemMatch']['cod'] = $roleCode;
        }
        $groups = array();
        $groups['$in'] = $user->getGroupsIds();
        $filter->setParameter('people', $people);
        $filter->setParameter('groups', $groups);
        $filter->setParameter('status', MultimediaObject::STATUS_PUBLISHED);
        $filter->setParameter('display_track_tag', 'display');
    }

    /**
     * Show modal to add one or more mmobjs to a playlist.
     *
     * @Template
     */
    public function addModalAction(Request $request)
    {
        $repoSeries = $this->getDoctrine()
                           ->getRepository('PumukitSchemaBundle:Series');
        $repoMms = $this->getDoctrine()
                        ->getRepository('PumukitSchemaBundle:MultimediaObject');

        $series = $repoSeries->createQueryBuilder()
                             ->field('type')->equals(Series::TYPE_PLAYLIST)
                             ->sort('public_date', -1)
                             ->getQuery()
                             ->execute();

        $multimediaObject = $request->get('id') ? $repoMms->find($request->get('id')) : null;

        $count = 0;
        if ($request->get('ids')) {
            $ids = json_decode($request->get('ids'));
            $count = count($ids);
        }

        return array(
            'series' => $series,
            'multimediaObject' => $multimediaObject,
            'id' => $request->get('id'),
            'ids' => $request->get('ids'),
            'num_mm' => $count,
            'locales' => $this->container->getParameter('pumukit2.locales'),
        );
    }

    /**
     * ids
     * id.
     *
     * series_id
     * series_ids
     */
    public function addBatchToSeveralPlaylistAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $seriesRepo = $dm->getRepository('PumukitSchemaBundle:Series');

        $mmobjIds = $this->getIds($request, 'ids');
        $playlistIds = $this->getIds($request, 'series_ids');

        $mmObjs = $mmobjRepo->findBy(array('_id' => array('$in' => $mmobjIds)));
        $playlists = $seriesRepo->findBy(array('_id' => array('$in' => $playlistIds)));

        foreach ($playlists as $playlist) {
            foreach ($mmObjs as $mmObj) {
                $playlist->getPlaylist()->addMultimediaObject($mmObj);
            }
            $dm->persist($playlist);
        }
        $dm->flush();

        return new JsonResponse(array());
    }

    /**
     * TODO.
     */
    private function getIds(Request $request, $idsKey = 'ids')
    {
        if ($request->request->has($idsKey)) {
            $ids = $request->request->get($idsKey);
            if ('string' === gettype($ids)) {
                return json_decode($ids, true);
            } else {
                return $ids;
            }
        }
        throw $this->createNotFoundException();
    }
}
