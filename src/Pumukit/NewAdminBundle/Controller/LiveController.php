<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\LiveBundle\Document\Live;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("is_granted('ROLE_ACCESS_LIVE_CHANNELS')")
 */
class LiveController extends AdminController implements NewAdminControllerInterface
{
    public static $resourceName = 'live';
    public static $repoName = Live::class;

    /**
     * Create Action
     * Overwrite to return json response
     * and update page.
     */
    public function createAction(Request $request)
    {
        $resource = $this->createNew();
        $form = $this->getForm($resource, $request->getLocale());

        if ($form->handleRequest($request)->isValid()) {
            $resource = $this->update($resource);

            if (null === $resource) {
                return new JsonResponse(['liveId' => null]);
            }
            $this->get('session')->set('admin/live/id', $resource->getId());

            return new JsonResponse(['liveId' => $resource->getId()]);
        }

        return $this->render(
            'PumukitNewAdminBundle:Live:create.html.twig',
            [
                'enableChat' => $this->container->getParameter('pumukit_live.chat.enable'),
                'live' => $resource,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Update Action
     * Overwrite to return list and not index
     * and show toast message.
     */
    public function updateAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $resourceName = $this->getResourceName();

        $resource = $this->findOr404($request);
        $form = $this->getForm($resource);

        if ($form->handleRequest($request)->isValid()) {
            try {
                $dm->persist($resource);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
        }

        return $this->render(
            'PumukitNewAdminBundle:'.ucfirst($resourceName).':update.html.twig',
            [
                'enableChat' => $this->container->getParameter('pumukit_live.chat.enable'),
                'live' => $resource,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Gets the list of resources according to a criteria.
     *
     * @param mixed $criteria
     */
    public function getResources(Request $request, $criteria)
    {
        $sorting = $this->getSorting();
        $session = $this->get('session');
        $session_namespace = 'admin/live';

        $newLiveId = $request->get('newLiveId');

        $resources = $this->createPager($criteria, $sorting);

        if ($request->get('page', null)) {
            $session->set($session_namespace.'/page', $request->get('page', 1));
        }
        $page = $session->get($session_namespace.'/page', 1);

        if ($request->get('paginate', null)) {
            $session->set($session_namespace.'/paginate', $request->get('paginate', 10));
        }

        $resources
            ->setMaxPerPage($session->get($session_namespace.'/paginate', 10))
            ->setNormalizeOutOfRangePages(true)
        ;

        if ($newLiveId && (($resources->getNbResults() / $resources->getMaxPerPage()) > $page)) {
            $page = $resources->getNbPages();
            $session->set($session_namespace.'/page', $page);
        }
        $resources->setCurrentPage($page);

        return $resources;
    }

    /**
     * Delete action.
     */
    public function deleteAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $resourceId = $resource->getId();
        $resourceName = $this->getResourceName();

        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $liveEvents = $dm->getRepository(MultimediaObject::class)->findOneBy(['embeddedEvent.live.$id' => new \MongoId($resourceId)]);
        if ($liveEvents) {
            return new JsonResponse(['error']);
        }

        if ($resourceId === $this->get('session')->get('admin/'.$resourceName.'/id')) {
            $this->get('session')->remove('admin/'.$resourceName.'/id');
        }

        $dm->remove($resource);
        $dm->flush();

        return new JsonResponse(['success']);
    }

    public function batchDeleteAction(Request $request)
    {
        $translator = $this->get('translator');

        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $resourceName = $this->getResourceName();

        $aResult = $this->checkEmptyChannels($ids);
        if (!$aResult['emptyChannels']) {
            return new Response($translator->trans('There are associated events on channel id'.$aResult['channelId']), Response::HTTP_BAD_REQUEST);
        }

        $factory = $this->get('pumukitschema.factory');
        foreach ($ids as $id) {
            $resource = $this->find($id);

            try {
                $factory->deleteResource($resource);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
            if ($id === $this->get('session')->get('admin/'.$resourceName.'/id')) {
                $this->get('session')->remove('admin/'.$resourceName.'/id');
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
    }

    public function createNew()
    {
        return new Live();
    }

    private function checkEmptyChannels($ids)
    {
        $emptyChannels = true;
        $channelId = null;
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        foreach ($ids as $id) {
            $liveEvents = $dm->getRepository(MultimediaObject::class)->findOneBy(['embeddedEvent.live.$id' => new \MongoId($id)]);
            if ($liveEvents) {
                $emptyChannels = false;
                $channelId = $id;

                break;
            }
        }

        return ['emptyChannels' => $emptyChannels, 'channelId' => $channelId];
    }
}
