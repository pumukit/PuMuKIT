<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\NewAdminBundle\Form\Type\SeriesType;
use Pumukit\NewAdminBundle\Form\Type\PlaylistType;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class PlaylistController extends SeriesController
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
        foreach($resources as $playlist) {
            if($playlist->getId() == $this->get('session')->get('admin/playlist/id')){
                $update_session = false;
            }
        }

        if($update_session){
            $this->get('session')->remove('admin/playlist/id');
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
        $criteria = $this->getCriteria($config);
        $selectedPlaylistId = $request->get('selectedPlaylistId', null);
        $resources = $this->getResources($request, $config, $criteria, $selectedPlaylistId);

        return array('series' => $resources);
    }

    /**
     * Create new resource
     */
    public function createAction(Request $request)
    {
        $factory = $this->get('pumukitschema.factory');
        $playlist = $factory->createSeries($this->getUser());
        $this->get('session')->set('admin/playlist/id', $playlist->getId());

        return new JsonResponse(array('playlistId' => $playlist->getId()));
    }

    /**
     * Display the form for editing or update the resource.
     */
    public function updateAction(Request $request)
    {
        $config = $this->getConfiguration();

        $resource = $this->findOr404($request);
        $this->get('session')->set('admin/playlist/id', $request->get('id'));

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $disablePudenew = !$this->container->getParameter('show_latest_with_pudenew');
        $form = $this->createForm(new PlaylistType($translator, $locale, $disablePudenew), $resource);

        $method = $request->getMethod();
        if (in_array($method, array('POST', 'PUT', 'PATCH')) &&
            $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            $this->domainManager->update($resource);
            $this->get('pumukitschema.playlist_dispatcher')->dispatchUpdate($resource);

            if ($config->isApiRequest()) {
                return $this->handleView($this->view($form));
            }

            $criteria = $this->getCriteria($config);
            $resources = $this->getResources($request, $config, $criteria, $resource->getId());

            return $this->render('PumukitNewAdminBundle:Playlist:list.html.twig',
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

        try {
            $personalScopeRole = $personService->getPersonalScopeRole();
        } catch (\Exception $e) {
            return new Response($e, Response::HTTP_BAD_REQUEST);
        }

        $roles = $personService->getRoles();
        if (null === $roles){
            throw new \Exception('Not found any role.');
        }

        $parentTags = $factoryService->getParentTags();
        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        return $this->render('PumukitNewAdminBundle:Playlist:update.html.twig',
                             array(
                                   'series'                   => $resource,
                                   'form'                     => $form->createView(),
//                                   'roles'                    => $roles,
//                                   'personal_scope_role'      => $personalScopeRole,
//                                   'personal_scope_role_code' => $personalScopeRoleCode,
//                                   'pub_decisions'            => $pubDecisionsTags,
//                                   'parent_tags'              => $parentTags,
                                   'template'                 => '_template'
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

        $playlist = $this->findOr404($request);
        if(!$this->isUserAllowedToDelete($playlist))
            return new Response('You don\'t have enough permissions to delete this playlist. Contact your administrator.', Response::HTTP_FORBIDDEN);

        $playlistId = $playlist->getId();

        $playlistSessionId = $this->get('session')->get('admin/mms/id');
        if ($playlistId === $playlistSessionId){
            $this->get('session')->remove('admin/playlist/id');
        }

        $mmSessionId = $this->get('session')->get('admin/mms/id');
        if ($mmSessionId){
            $mm = $factoryService->findMultimediaObjectById($mmSessionId);
            if ($playlistId === $mm->getPlaylist()->getId()){
                $this->get('session')->remove('admin/mms/id');
            }
        }

        try {
            $factoryService->deletePlaylist($playlist);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        if ($config->isApiRequest()) {
            return $this->handleView($this->view());
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_list', array()));
    }

    /**
     * Batch delete action
     * Overwrite to delete multimedia objects inside playlist
     */
    public function batchDeleteAction(Request $request)
    {
        $factoryService = $this->get('pumukitschema.factory');

        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)){
            $ids = json_decode($ids, true);
        }

        foreach ($ids as $id) {
            $playlist = $this->find($id);
            if(!$this->isUserAllowedToDelete($playlist))
                continue;
            $playlistId = $playlist->getId();

            $playlistSessionId = $this->get('session')->get('admin/mms/id');
            if ($playlistId === $playlistSessionId){
                $this->get('session')->remove('admin/playlist/id');
            }

            $mmSessionId = $this->get('session')->get('admin/mms/id');
            if ($mmSessionId){
                $mm = $factoryService->findMultimediaObjectById($mmSessionId);
                if ($playlistId === $mm->getPlaylist()->getId()){
                    $this->get('session')->remove('admin/mms/id');
                }
            }

            try {
                $factoryService->deleteSeries($playlist);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_list', array()));
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

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_list'));
    }

    /**
     * Change publication form
     * @Template("PumukitNewAdminBundle:Playlist:changepub.html.twig")
     */
    public function changePubAction(Request $request)
    {
        $playlist = $this->findOr404($request);

        $mmStatus = array(
                        'published' => MultimediaObject::STATUS_PUBLISHED,
                        'blocked' => MultimediaObject::STATUS_BLOQ,
                        'hidden' => MultimediaObject::STATUS_HIDE
                        );

        $pubChannels = $this->get('pumukitschema.factory')->getTagsByCod('PUBCHANNELS', true);

        return array(
                     'playlist' => $playlist,
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

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_list'));
    }


    /**
     * Helper for the menu search form
     */
    public function searchAction(Request $req)
    {
        $q = $req->get('q');
        $this->get('session')->set('admin/playlist/criteria', array('search' => $q));

        return $this->redirect($this->generateUrl('pumukitnewadmin_playlist_index'));
    }
}
