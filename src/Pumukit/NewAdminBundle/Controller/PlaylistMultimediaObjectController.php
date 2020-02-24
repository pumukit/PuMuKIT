<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\NewAdminBundle\Services\MultimediaObjectSearchService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\SchemaBundle\Services\PersonService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PlaylistMultimediaObjectController extends AbstractController
{
    /** @var SessionInterface */
    private $session;
    /** @var FactoryService */
    private $factoryService;
    /** @var PersonService */
    private $personService;
    /** @var MultimediaObjectService */
    private $multimediaObjectService;
    /** @var DocumentManager */
    private $documentManager;
    /** @var PaginationService */
    private $paginationService;
    /** @var MultimediaObjectSearchService */
    private $multimediaObjectSearchService;
    /** @var EmbeddedBroadcastService */
    private $embeddedBroadcastService;
    /** @var RouterInterface */
    private $router;
    /** @var TokenStorageInterface */
    private $securityTokenStorage;
    private $warningOnUnpublished;
    private $locales;

    public function __construct(
        SessionInterface $session,
        FactoryService $factoryService,
        PersonService $personService,
        MultimediaObjectService $multimediaObjectService,
        DocumentManager $documentManager,
        PaginationService $paginationService,
        MultimediaObjectSearchService $multimediaObjectSearchService,
        EmbeddedBroadcastService $embeddedBroadcastService,
        RouterInterface $router,
        TokenStorageInterface $securityTokenStorage,
        $warningOnUnpublished,
        $locales
    ) {
        $this->session = $session;
        $this->factoryService = $factoryService;
        $this->personService = $personService;
        $this->multimediaObjectService = $multimediaObjectService;
        $this->documentManager = $documentManager;
        $this->paginationService = $paginationService;
        $this->warningOnUnpublished = $warningOnUnpublished;
        $this->multimediaObjectSearchService = $multimediaObjectSearchService;
        $this->embeddedBroadcastService = $embeddedBroadcastService;
        $this->router = $router;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->locales = $locales;
    }

    /**
     * @Template("@PumukitNewAdmin/PlaylistMultimediaObject/index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $session = $this->session;
        $sessionId = $session->get('admin/playlist/id', null);
        $series = $this->factoryService->findSeriesById($request->query->get('id'), $sessionId);
        if (!$series) {
            throw $this->createNotFoundException();
        }

        $session->set('admin/playlist/id', $series->getId());
        if ($request->query->has('mmid')) {
            $session->set('admin/playlistmms/id', $request->query->get('mmid'));
        }

        $mms = $this->getPlaylistMmobjs($series, $request);

        // Removes the session mmobj (shown on info and preview) if it does not belong to THIS playlist.
        $update_session = true;
        foreach ($mms as $mm) {
            if ($mm->getId() == $this->session->get('admin/playlistmms/id')) {
                $update_session = false;

                break;
            }
        }
        if ($update_session) {
            $session->remove('admin/playlistmms/id');
        }

        return [
            'playlist' => $series,
            'mms' => $mms,
        ];
    }

    /**
     * Displays the preview.
     *
     * @Template("@PumukitNewAdmin/MultimediaObject/show.html.twig")
     */
    public function showAction(MultimediaObject $mmobj, Request $request)
    {
        $this->session->set('admin/playlistmms/id', $mmobj->getId());
        if ($request->query->has('pos')) {
            $this->session->set('admin/playlistmms/pos', $request->query->get('pos'));
        }
        $roles = $this->personService->getRoles();
        $activeEditor = $this->checkHasEditor();

        return [
            'mm' => $mmobj,
            'roles' => $roles,
            'active_editor' => $activeEditor,
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/PlaylistMultimediaObject/info.html.twig")
     */
    public function infoAction(MultimediaObject $mmobj, Request $request): array
    {
        return [
            'mm' => $mmobj,
            'is_published' => $this->multimediaObjectService->isPublished($mmobj, 'PUCHWEBTV'),
            'is_hidden' => $this->multimediaObjectService->isHidden($mmobj, 'PUCHWEBTV'),
            'is_playable' => $this->multimediaObjectService->hasPlayableResource($mmobj),
            'warning_on_unpublished' => $this->warningOnUnpublished,
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/PlaylistMultimediaObject/list.html.twig")
     */
    public function listAction(Request $request)
    {
        $sessionId = $this->session->get('admin/playlist/id', null);
        $series = $this->factoryService->findSeriesById($request->query->get('id'), $sessionId);
        if (!$series) {
            throw $this->createNotFoundException();
        }

        $this->session->set('admin/playlist/id', $series->getId());

        if ($request->query->has('mmid')) {
            $this->session->set('admin/playlistmms/id', $request->query->get('mmid'));
        }

        $mms = $this->getPlaylistMmobjs($series, $request);

        return [
            'playlist' => $series,
            'mms' => $mms,
        ];
    }

    /**
     * Returns a modal window where to add mmobjs to a playlist.
     *
     * It is meant to be used through ajax.
     *
     * @Template("@PumukitNewAdmin/PlaylistMultimediaObject/modal.html.twig")
     */
    public function modalAction(Series $playlist, Request $request)
    {
        $limit = $request->get('modal_limit', 20);
        //Get all multimedia objects. The filter will do the rest.
        $mmobjs = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();
        $total = $mmobjs->count()->getQuery()->execute();

        return [
            'my_mmobjs' => [],
            'mmobjs_total' => $total,
            'mmobjs_limit' => $limit,
            'playlist' => $playlist,
        ];
    }

    /**
     * Returns the user mmobjs.
     *
     * It is meant to be used through ajax.
     *
     * @Template("@PumukitNewAdmin/PlaylistMultimediaObject/modal_myvideos_list.html.twig")
     */
    public function modalMyMmobjsAction(Series $playlist, Request $request)
    {
        $page = $request->get('modal_page', 1);
        $limit = $request->get('modal_limit', 20);
        //Get all multimedia objects. The filter will do the rest.
        $mmobjs = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();

        $pager = $this->paginationService->createDoctrineODMMongoDBAdapter($mmobjs, $page, $limit);

        return [
            'my_mmobjs' => $pager,
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/PlaylistMultimediaObject/modal_search_list.html.twig")
     */
    public function searchModalAction(Request $request)
    {
        $this->enableFilter();
        $limit = 50;
        $value = $request->query->get('search', '');

        $criteria = ['search' => $value];
        $criteria = $this->multimediaObjectSearchService->processMMOCriteria($criteria, $request->getLocale());

        $queryBuilder = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();
        $criteria = array_merge($queryBuilder->getQueryArray(), $criteria);
        $queryBuilder->setQueryArray($criteria);
        $queryBuilder->limit($limit);
        $queryBuilder->sortMeta('score', 'textScore');

        $pager = $this->paginationService->createDoctrineODMMongoDBAdapter($queryBuilder, 1, $limit);

        return ['mmobjs' => $pager];
    }

    /**
     * @Template("@PumukitNewAdmin/PlaylistMultimediaObject/modal_url_list.html.twig")
     */
    public function urlModalAction(Request $request)
    {
        $broadcastService = $this->embeddedBroadcastService;

        $this->enableFilter();
        $id = $request->query->get('mmid', '');
        $mmobj = $this->documentManager->getRepository(MultimediaObject::class)->find($id);
        $user = $this->getUser();
        $canBePlayed = null;
        $canUserPlay = null;
        if ($mmobj) {
            $canBePlayed = $broadcastService->canUserPlayMultimediaObject($mmobj, $user);
            $canUserPlay = $this->multimediaObjectService->canBeDisplayed($mmobj, 'PUCHWEBTV');
        }
        if ($mmobj && (!$canBePlayed || !$canUserPlay)) {
            $mmobj = null;
        }

        return [
            'mmobj' => $mmobj,
            'mmobj_id' => $id,
        ];
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

        $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);
        foreach ($mmobjIds as $id) {
            $mmobj = $mmobjRepo->find($id);
            if (!$mmobj) {
                continue;
            }
            $playlist->getPlaylist()->addMultimediaObject($mmobj);
            $this->documentManager->persist($playlist);
        }
        $this->documentManager->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlistmms_list', ['id' => $playlist->getId()]));
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

        $mms = $playlist->getPlaylist()->getMultimediaObjects();
        foreach ($mmobjIds as $pos => $id) {
            if (isset($mms[$pos]) && $mms[$pos]->getId() == $id) {
                $playlist->getPlaylist()->removeMultimediaObjectByPos($pos);
            }
        }
        $this->documentManager->persist($playlist);
        $this->documentManager->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlistmms_list', ['id' => $playlist->getId()]));
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

        $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);

        $playlistEmbed = $series->getPlaylist();
        $mmobjId = $request->query->get('mm_id');
        $mm = $mmobjRepo->find($mmobjId);
        if (!$mm) {
            throw new \Exception("The id: {$mmobjId} , does not belong to any Multimedia Object");
        }

        $playlistEmbed->addMultimediaObject($mm);
        $this->documentManager->persist($playlistEmbed);
        $this->documentManager->flush();
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

    /**
     * Show modal to add one or more mmobjs to a playlist.
     *
     * @Template("@PumukitNewAdmin/PlaylistMultimediaObject/addModal.html.twig")
     */
    public function addModalAction(Request $request)
    {
        $repoSeries = $this->documentManager->getRepository(Series::class);
        $repoMms = $this->documentManager->getRepository(MultimediaObject::class);

        $series = $repoSeries->createQueryBuilder()
            ->field('type')->equals(Series::TYPE_PLAYLIST)
            ->sort('public_date', -1)
            ->getQuery()
            ->execute()
        ;

        $multimediaObject = $request->get('id') ? $repoMms->find($request->get('id')) : null;

        $count = 0;
        if ($request->get('ids')) {
            $ids = json_decode($request->get('ids'));
            $count = count($ids);
        }

        return [
            'series' => $series,
            'multimediaObject' => $multimediaObject,
            'id' => $request->get('id'),
            'ids' => $request->get('ids'),
            'num_mm' => $count,
            'locales' => $this->locales,
        ];
    }

    public function addBatchToSeveralPlaylistAction(Request $request)
    {
        $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);
        $seriesRepo = $this->documentManager->getRepository(Series::class);

        $mmobjIds = $this->getIds($request, 'ids');
        $playlistIds = $this->getIds($request, 'series_ids');

        $mmObjs = $mmobjRepo->findBy(['_id' => ['$in' => $mmobjIds]]);
        $playlists = $seriesRepo->findBy(['_id' => ['$in' => $playlistIds]]);

        foreach ($playlists as $playlist) {
            foreach ($mmObjs as $mmObj) {
                $playlist->getPlaylist()->addMultimediaObject($mmObj);
            }
            $this->documentManager->persist($playlist);
        }
        $this->documentManager->flush();

        return new JsonResponse([]);
    }

    protected function getPlaylistMmobjs(Series $series, $request)
    {
        $mmsList = $series->getPlaylist()->getMultimediaObjects();

        $session = $this->session;
        if ($request->get('page', null)) {
            $session->set('admin/playlistmms/page', $request->get('page', 1));
        }

        if ($request->get('paginate', null)) {
            $session->set('admin/playlistmms/paginate', $request->get('paginate', 10));
        }

        $page = $session->get('admin/playlistmms/page', 1);
        $limit = $session->get('admin/playlistmms/paginate', 10);

        return $this->paginationService->createDoctrineCollectionAdapter($mmsList, $page, $limit);
    }

    protected function moveAction(Series $playlist, $initPos, $endPos)
    {
        $actionResponse = $this->redirect($this->generateUrl('pumukitnewadmin_playlistmms_index', ['id' => $playlist->getId()]));

        $playlist->getPlaylist()->moveMultimediaObject($initPos, $endPos);
        $this->documentManager->persist($playlist);
        $this->documentManager->flush();

        return $actionResponse;
    }

    //Workaround function to check if the VideoEditorBundle is installed.
    protected function checkHasEditor()
    {
        $routes = $this->router->getRouteCollection()->all();

        return array_key_exists('pumukit_videoeditor_index', $routes);
    }

    //Disables the standard backoffice filter and enables the 'personal' filter. (Check own videos or public videos)
    protected function enableFilter(): void
    {
        $user = $this->securityTokenStorage->getToken()->getUser();
        if (!$user instanceof User || $this->isGranted(PermissionProfile::SCOPE_GLOBAL)) {
            return;
        }
        if ($this->documentManager->getFilterCollection()->isEnabled('backoffice')) {
            $this->documentManager->getFilterCollection()->disable('backoffice');
        }
        $filter = $this->documentManager->getFilterCollection()->enable('personal');
        $person = $this->personService->getPersonFromLoggedInUser($user);
        $people = [];
        if ((null !== $person) && (null !== ($roleCode = $this->personService->getPersonalScopeRoleCode()))) {
            $people['$elemMatch'] = [];
            $people['$elemMatch']['people._id'] = new ObjectId($person->getId());
            $people['$elemMatch']['cod'] = $roleCode;
        }
        $groups = [];
        $groups['$in'] = $user->getGroupsIds();
        $filter->setParameter('people', $people);
        $filter->setParameter('groups', $groups);
        $filter->setParameter('status', MultimediaObject::STATUS_PUBLISHED);
        $filter->setParameter('display_track_tag', 'display');
    }

    private function getIds(Request $request, $idsKey = 'ids')
    {
        if ($request->request->has($idsKey)) {
            $ids = $request->request->get($idsKey);
            if ('string' === gettype($ids)) {
                return json_decode($ids, true);
            }

            return $ids;
        }

        throw $this->createNotFoundException();
    }
}
