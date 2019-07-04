<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\NewAdminBundle\Form\Type\PlaylistType;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @Security("is_granted('ROLE_ACCESS_EDIT_PLAYLIST')")
 */
class PlaylistController extends CollectionController
{
    /**
     * @Template("PumukitNewAdminBundle:Collection:show.html.twig")
     */
    public function showAction(Series $collection, Request $request)
    {
        $this->get('session')->set('admin/playlist/id', $collection->getId());

        return ['collection' => $collection];
    }

    /**
     * Overwrite to search criteria with date.
     *
     * @Template
     */
    public function indexAction(Request $request)
    {
        $update_session = true;
        $resources = $this->getResources($request);

        foreach ($resources as $playlist) {
            if ($playlist->getId() == $this->get('session')->get('admin/playlist/id')) {
                $update_session = false;
            }
        }

        if ($update_session) {
            $this->get('session')->remove('admin/playlist/id');
        }

        return ['series' => $resources];
    }

    /**
     * List action.
     *
     * @Template
     */
    public function listAction(Request $request)
    {
        $resources = $this->getResources($request);

        return ['series' => $resources];
    }

    /**
     * Create new resource.
     */
    public function createAction(Request $request)
    {
        $factory = $this->get('pumukitschema.factory');
        $collection = $factory->createPlaylist($this->getUser(), $request->request->get('playlist_title'));
        $this->get('session')->set('admin/playlist/id', $collection->getId());

        return new JsonResponse(['playlistId' => $collection->getId(), 'title' => $collection->getTitle($request->getLocale())]);
    }

    /**
     * Display the form for editing or update the resource.
     */
    public function updateAction(Series $series, Request $request)
    {
        $this->get('session')->set('admin/playlist/id', $series->getId());

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $form = $this->createForm(PlaylistType::class, $series, ['translator' => $translator, 'locale' => $locale]);

        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH']) &&
            $form->handleRequest($request)->isValid()) {
            $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $dm->persist($series);
            $dm->flush();
            $this->get('pumukitschema.series_dispatcher')->dispatchUpdate($series);
            $resources = $this->getResources($request);

            return $this->render('PumukitNewAdminBundle:Playlist:list.html.twig',
                                 ['series' => $resources]
            );
        }

        return $this->render('PumukitNewAdminBundle:Playlist:update.html.twig',
                             [
                                 'series' => $series,
                                 'form' => $form->createView(),
                                 'template' => '_template',
                             ]
        );
    }

    /**
     * @param Series  $playlist
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function deleteAction(Series $playlist, Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');

        if (!$this->isUserAllowedToDelete($playlist)) {
            return new Response('You don\'t have enough permissions to delete this playlist. Contact your administrator.', Response::HTTP_FORBIDDEN);
        }

        try {
            $factoryService->deleteSeries($playlist);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $playlistId = $playlist->getId();
        $playlistSessionId = $this->get('session')->get('admin/mms/id');
        if ($playlistId === $playlistSessionId) {
            $this->get('session')->remove('admin/playlist/id');
        }
        $mmSessionId = $this->get('session')->get('admin/mms/id');
        if ($mmSessionId) {
            $mm = $factoryService->findMultimediaObjectById($mmSessionId);
            if ($playlistId === $mm->getSeries()->getId()) {
                $this->get('session')->remove('admin/mms/id');
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_list', []));
    }

    /**
     * Batch delete action
     * Overwrite to delete multimedia objects inside playlist.
     */
    public function batchDeleteAction(Request $request)
    {
        $ids = $request->get('ids');
        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $this->batchDeleteCollection($ids);

        // Removes ids on session (if the series/mmobj does not exist now, we should get rid of the stored id)
        $seriesRepo = $this->get('doctrine_mongodb.odm.document_manager')
                    ->getRepository(Series::class);
        $mmobjRepo = $this->get('doctrine_mongodb.odm.document_manager')
                   ->getRepository(MultimediaObject::class);

        //        $this->get('doctrine_mongodb.odm.document_manager')->clear();
        $playlist = $seriesRepo->find($this->get('session')->get('admin/playlist/id'));
        if (!$playlist) {
            $this->get('session')->remove('admin/playlist/id');
        }
        $mm = $mmobjRepo->find($this->get('session')->get('admin/mms/id'));
        if (!$mm) {
            $this->get('session')->remove('admin/mms/id');
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_list', []));
    }

    /**
     * Helper for the menu search form.
     */
    public function searchAction(Request $req)
    {
        $q = $req->get('q');
        $this->get('session')->set('admin/playlist/criteria', ['search' => $q]);

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_index'));
    }

    /**
     * Helper to get all series of type playlist.
     */
    protected function getResources(Request $request)
    {
        $sorting = $this->getSorting($request);
        $criteria = $this->getCriteria($request);
        $criteria = array_merge($criteria, ['type' => Series::TYPE_PLAYLIST]);
        $queryBuilder = $this->get('doctrine_mongodb.odm.document_manager')->getRepository(Series::class)->createQueryBuilder();
        $queryBuilder->setQueryArray($criteria);
        //Sort playlist
        $queryBuilder->sort($sorting);
        $resources = $this->createPager($queryBuilder, $request, 'admin/playlist');

        return $resources;
    }

    /**
     * Gets the criteria values.
     */
    public function getCriteria(Request $request)
    {
        $criteria = $request->get('criteria', []);

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/playlist/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/playlist/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/playlist/criteria', []);

        $new_criteria = $this->get('pumukitnewadmin.series_search')->processCriteria($criteria, true, $request->getLocale());

        return $new_criteria;
    }

    /**
     * Gets the sorting values from the request and initialize session vars accordingly if necessary.
     */
    private function getSorting(Request $request = null, $session_namespace = null)
    {
        $session = $this->get('session');

        if ($sorting = $request->get('sorting')) {
            $session->set('admin/playlist/type', current($sorting));
            $session->set('admin/playlist/sort', key($sorting));
        }

        $value = $session->get('admin/playlist/type', 'desc');
        $key = $session->get('admin/playlist/sort', 'public_date');

        if ('title' == $key) {
            $key .= '.'.$request->getLocale();
        }

        return  [$key => $value];
    }
}
