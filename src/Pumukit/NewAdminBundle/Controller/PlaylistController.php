<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\NewAdminBundle\Form\Type\PlaylistType;
use Pumukit\NewAdminBundle\Services\SeriesSearchService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\SeriesEventDispatcherService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_EDIT_PLAYLIST')")
 */
class PlaylistController extends CollectionController
{
    /** @var SessionInterface */
    private $session;
    /** @var TranslatorInterface */
    private $translator;
    /** @var SeriesEventDispatcherService */
    private $pumukitSchemaSeriesEventDispatcher;
    /** @var SeriesSearchService */
    private $seriesSearchService;

    public function __construct(
        SessionInterface $session,
        TranslatorInterface $translator,
        SeriesEventDispatcherService $pumukitSchemaSeriesEventDispatcher,
        SeriesSearchService $seriesSearchService,
        DocumentManager $documentManager,
        FactoryService $factoryService,
        PaginationService $paginationService,
        PersonService $personService
    ) {
        parent::__construct($documentManager, $factoryService, $paginationService, $personService, $session);
        $this->session = $session;
        $this->translator = $translator;
        $this->pumukitSchemaSeriesEventDispatcher = $pumukitSchemaSeriesEventDispatcher;
        $this->seriesSearchService = $seriesSearchService;
    }

    /**
     * @Template("PumukitNewAdminBundle:Collection:show.html.twig")
     */
    public function showAction(Series $collection): array
    {
        $this->session->set('admin/playlist/id', $collection->getId());

        return ['collection' => $collection];
    }

    /**
     * @Template("PumukitNewAdminBundle:Playlist:index.html.twig")
     */
    public function indexAction(Request $request): array
    {
        $update_session = true;
        $resources = $this->getResources($request);

        foreach ($resources as $playlist) {
            if ($playlist->getId() == $this->session->get('admin/playlist/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $this->session->remove('admin/playlist/id');
        }

        return ['series' => $resources];
    }

    /**
     * @Template("PumukitNewAdminBundle:Playlist:list.html.twig")
     */
    public function listAction(Request $request): array
    {
        $resources = $this->getResources($request);

        return ['series' => $resources];
    }

    public function createAction(Request $request): JsonResponse
    {
        $collection = $this->factoryService->createPlaylist($this->getUser(), $request->request->get('playlist_title'));
        $this->session->set('admin/playlist/id', $collection->getId());

        return new JsonResponse(['playlistId' => $collection->getId(), 'title' => $collection->getTitle($request->getLocale())]);
    }

    public function updateAction(Request $request, Series $series): Response
    {
        $this->session->set('admin/playlist/id', $series->getId());

        $locale = $request->getLocale();
        $form = $this->createForm(PlaylistType::class, $series, ['translator' => $this->translator, 'locale' => $locale]);

        $method = $request->getMethod();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $this->documentManager->persist($series);
            $this->documentManager->flush();
            $this->pumukitSchemaSeriesEventDispatcher->dispatchUpdate($series);
            $resources = $this->getResources($request);

            return $this->render(
                'PumukitNewAdminBundle:Playlist:list.html.twig',
                ['series' => $resources]
            );
        }

        return $this->render(
            'PumukitNewAdminBundle:Playlist:update.html.twig',
            [
                'series' => $series,
                'form' => $form->createView(),
                'template' => '_template',
            ]
        );
    }

    public function deleteAction(Series $playlist)
    {
        if (!$this->isUserAllowedToDelete($playlist)) {
            return new Response('You don\'t have enough permissions to delete this playlist. Contact your administrator.', Response::HTTP_FORBIDDEN);
        }

        try {
            $this->factoryService->deleteSeries($playlist);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $playlistId = $playlist->getId();
        $playlistSessionId = $this->session->get('admin/mms/id');
        if ($playlistId === $playlistSessionId) {
            $this->session->remove('admin/playlist/id');
        }
        $mmSessionId = $this->session->get('admin/mms/id');
        if ($mmSessionId) {
            $mm = $this->factoryService->findMultimediaObjectById($mmSessionId);
            if ($playlistId === $mm->getSeries()->getId()) {
                $this->session->remove('admin/mms/id');
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_list', []));
    }

    public function batchDeleteAction(Request $request): RedirectResponse
    {
        $ids = $request->get('ids');
        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $this->batchDeleteCollection($ids);

        // Removes ids on session (if the series/mmobj does not exist now, we should get rid of the stored id)
        $seriesRepo = $this->documentManager->getRepository(Series::class);
        $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);

        $playlist = $seriesRepo->find($this->session->get('admin/playlist/id'));
        if (!$playlist) {
            $this->session->remove('admin/playlist/id');
        }
        $mm = $mmobjRepo->find($this->session->get('admin/mms/id'));
        if (!$mm) {
            $this->session->remove('admin/mms/id');
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_list', []));
    }

    public function searchAction(Request $req): RedirectResponse
    {
        $q = $req->get('q');
        $this->session->set('admin/playlist/criteria', ['search' => $q]);

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_index'));
    }

    public function getCriteria(Request $request): array
    {
        $criteria = $request->get('criteria', []);

        if (array_key_exists('reset', $criteria)) {
            $this->session->remove('admin/playlist/criteria');
        } elseif ($criteria) {
            $this->session->set('admin/playlist/criteria', $criteria);
        }
        $criteria = $this->session->get('admin/playlist/criteria', []);

        return $this->seriesSearchService->processCriteria($criteria, true, $request->getLocale());
    }

    protected function getResources(Request $request)
    {
        $sorting = $this->getSorting($request);
        $criteria = $this->getCriteria($request);
        $criteria = array_merge($criteria, ['type' => Series::TYPE_PLAYLIST]);
        $queryBuilder = $this->documentManager->getRepository(Series::class)->createQueryBuilder();
        $queryBuilder->setQueryArray($criteria);
        $queryBuilder->sort($sorting);

        return $this->createPager($queryBuilder, $request, 'admin/playlist');
    }

    private function getSorting(Request $request = null): array
    {
        if ($sorting = $request->get('sorting')) {
            $this->session->set('admin/playlist/type', current($sorting));
            $this->session->set('admin/playlist/sort', key($sorting));
        }

        $value = $this->session->get('admin/playlist/type', 'desc');
        $key = $this->session->get('admin/playlist/sort', 'public_date');

        if ('title' == $key) {
            $key .= '.'.$request->getLocale();
        }

        return [$key => $value];
    }
}
