<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_LIVE_CHANNELS')")
 */
class LiveController extends AdminController
{
    public static $resourceName = 'live';
    public static $repoName = Live::class;

    private $pumukitLiveChatEnable;
    private $advanceLiveEvent;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        UserService $userService,
        TranslatorInterface $translator,
        SessionInterface $session,
        $pumukitLiveChatEnable,
        $advanceLiveEvent
    ) {
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService, $session, $translator);
        $this->pumukitLiveChatEnable = $pumukitLiveChatEnable;
        $this->advanceLiveEvent = $advanceLiveEvent;
    }

    public function createAction(Request $request)
    {
        $resource = $this->createNew();
        $form = $this->getForm($resource, $request->getLocale());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->update($resource);

            if (null === $resource) {
                return new JsonResponse(['liveId' => null]);
            }
            $this->session->set('admin/live/id', $resource->getId());

            return new JsonResponse(['liveId' => $resource->getId()]);
        }

        return $this->render(
            '@PumukitNewAdmin/Live/create.html.twig',
            [
                'enableChat' => $this->pumukitLiveChatEnable,
                'advanceLiveEvent' => $this->advanceLiveEvent,
                'live' => $resource,
                'form' => $form->createView(),
            ]
        );
    }

    public function updateAction(Request $request)
    {
        $resourceName = $this->getResourceName();

        $resource = $this->findOr404($request);
        $form = $this->getForm($resource);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->documentManager->persist($resource);
                $this->documentManager->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
        }

        return $this->render(
            '@PumukitNewAdmin/'.ucfirst($resourceName).'/update.html.twig',
            [
                'enableChat' => $this->pumukitLiveChatEnable,
                'advanceLiveEvent' => $this->advanceLiveEvent,
                'live' => $resource,
                'form' => $form->createView(),
            ]
        );
    }

    public function getResources(Request $request, $criteria)
    {
        $sorting = $this->getSorting();
        $session = $this->session;
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

    public function deleteAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $resourceId = $resource->getId();
        $resourceName = $this->getResourceName();

        $liveEvents = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['embeddedEvent.live.$id' => new ObjectId($resourceId)]);
        if ($liveEvents) {
            return new JsonResponse(['error']);
        }

        if ($resourceId === $this->session->get('admin/'.$resourceName.'/id')) {
            $this->session->remove('admin/'.$resourceName.'/id');
        }

        $this->documentManager->remove($resource);
        $this->documentManager->flush();

        return new JsonResponse(['success']);
    }

    public function batchDeleteAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $resourceName = $this->getResourceName();

        $aResult = $this->checkEmptyChannels($ids);
        if (!$aResult['emptyChannels']) {
            return new Response($this->translator->trans('There are associated events on channel id'.$aResult['channelId']), Response::HTTP_BAD_REQUEST);
        }

        foreach ($ids as $id) {
            $resource = $this->find($id);

            try {
                $this->factoryService->deleteResource($resource);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
            if ($id === $this->session->get('admin/'.$resourceName.'/id')) {
                $this->session->remove('admin/'.$resourceName.'/id');
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
    }

    public function createNew()
    {
        return new Live();
    }

    private function checkEmptyChannels($ids): array
    {
        $emptyChannels = true;
        $channelId = null;

        foreach ($ids as $id) {
            $liveEvents = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['embeddedEvent.live.$id' => new ObjectId($id)]);
            if ($liveEvents) {
                $emptyChannels = false;
                $channelId = $id;

                break;
            }
        }

        return ['emptyChannels' => $emptyChannels, 'channelId' => $channelId];
    }
}
