<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\EmbeddedBroadcastService;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_GROUPS')")
 */
class GroupController extends AdminController
{
    public static $resourceName = 'group';
    public static $repoName = Group::class;

    /** @var MultimediaObjectService */
    private $multimediaObjectService;
    /** @var EmbeddedBroadcastService */
    private $embeddedBroadcastService;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        SessionInterface $session,
        MultimediaObjectService $multimediaObjectService,
        EmbeddedBroadcastService $embeddedBroadcastService,
        UserService $userService,
        TranslatorInterface $translator
    ) {
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService, $session, $translator);
        $this->documentManager = $documentManager;
        $this->groupService = $groupService;
        $this->session = $session;
        $this->multimediaObjectService = $multimediaObjectService;
        $this->embeddedBroadcastService = $embeddedBroadcastService;
        $this->userService = $userService;
    }

    /**
     * @Template("@PumukitNewAdmin/Group/index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $groups = $this->getResources($request, $criteria);

        $origins = $this->documentManager->createQueryBuilder(Group::class)->distinct('origin')->getQuery()->execute();

        return ['groups' => $groups, 'origins' => $origins];
    }

    /**
     * @Template("@PumukitNewAdmin/Group/list.html.twig")
     */
    public function listAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $groups = $this->getResources($request, $criteria);

        return ['groups' => $groups];
    }

    public function createAction(Request $request)
    {
        $group = $this->createNew();
        $form = $this->getForm($group, $request->getLocale());

        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->groupService->create($group);
                } catch (\Exception $e) {
                    return new JsonResponse([$e->getMessage()], Response::HTTP_BAD_REQUEST);
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_group_list'));
            }

            return new JsonResponse(['Form not valid'], Response::HTTP_BAD_REQUEST);
        }

        return $this->render(
            '@PumukitNewAdmin/Group/create.html.twig',
            [
                'group' => $group,
                'form' => $form->createView(),
            ]
        );
    }

    public function updateAction(Request $request)
    {
        $group = $this->findOr404($request);
        if (!$group->isLocal()) {
            return new Response('Not allowed to update not local Group', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $form = $this->getForm($group, $request->getLocale());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            try {
                $group = $this->groupService->update($group);
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_group_list'));
        }

        return $this->render(
            '@PumukitNewAdmin/Group/update.html.twig',
            [
                'group' => $group,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Template("@PumukitNewAdmin/Group/list.html.twig")
     */
    public function deleteAction(Request $request)
    {
        $group = $this->groupService->findById($request->get('id'));

        try {
            $this->groupService->delete($group);
        } catch (\Exception $e) {
            return new Response("Can not delete Group '".$group->getName()."'. ".$e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_list'));
    }

    public function batchDeleteAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $notDeleted = [];
        foreach ($ids as $id) {
            $group = $this->groupService->findById($id);

            try {
                $this->groupService->delete($group);
            } catch (\Exception $e) {
                if (0 === strpos($e->getMessage(), 'Not allowed to delete')) {
                    $notDeleted[] = $group->getKey();
                } else {
                    return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
                }
            }
            if ($id === $this->session->get('admin/group/id')) {
                $this->session->remove('admin/group/id');
            }
        }
        if ($notDeleted) {
            $code = Response::HTTP_BAD_REQUEST;
            $message = $this->translator->trans('Not allowed to delete Groups:');
            foreach ($notDeleted as $key) {
                if ($key === reset($notDeleted)) {
                    $message = $message.' ';
                } elseif ($key === end($notDeleted)) {
                    $message = $message.' and ';
                } else {
                    $message = $message.', ';
                }
                $message = $message.$key;
            }
        } else {
            $code = Response::HTTP_OK;
            $message = $this->translator->trans('Groups successfully deleted');
        }

        return new JsonResponse($message, $code);
    }

    public function getResources(Request $request, $criteria)
    {
        $sorting = $this->getSorting($request);
        $session = $this->session;
        $sessionNamespace = 'admin/group';

        $resources = $this->createPager($criteria, $sorting);

        if ($request->get('page', null)) {
            $session->set($sessionNamespace.'/page', $request->get('page', 1));
        }

        if ($request->get('paginate', null)) {
            $session->set($sessionNamespace.'/paginate', $request->get('paginate', 10));
        }

        $resources
            ->setMaxPerPage($session->get($sessionNamespace.'/paginate', 10))
            ->setNormalizeOutOfRangePages(true)
            ->setCurrentPage($session->get($sessionNamespace.'/page', 1))
            ;

        return $resources;
    }

    public function getSorting(Request $request = null, $session_namespace = null): array
    {
        $session = $this->session;
        if ($sorting = $request->get('sorting')) {
            $session->set('admin/group/type', $sorting[key($sorting)]);
            $session->set('admin/group/sort', key($sorting));
        }
        $value = $session->get('admin/group/type', 'asc');
        $key = $session->get('admin/group/sort', 'name');

        return [$key => $value];
    }

    /**
     * @Template("@PumukitNewAdmin/Group/info.html.twig")
     */
    public function infoAction(Request $request)
    {
        $group = $this->findOr404($request);
        $locale = $request->getLocale();
        $action = $request->get('action', false);
        $usersSort = ['username' => 1];
        $limit = 101;
        $users = $this->groupService->findUsersInGroup($group, $usersSort, $limit);
        $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);
        if ($locale) {
            $sort = ['title.'.$locale => 1];
        } else {
            $sort = ['title' => 1];
        }
        $adminMultimediaObjects = $mmobjRepo->findWithGroup($group, $sort, $limit);
        $viewerMultimediaObjects = $mmobjRepo->findWithGroupInEmbeddedBroadcast($group, $sort, $limit);
        $countResources = $this->groupService->countResourcesInGroup($group);
        $canBeDeleted = $this->groupService->canBeDeleted($group);
        $deleteMessage = $this->groupService->getDeleteMessage($group, $locale);

        return [
            'group' => $group,
            'action' => $action,
            'users' => $users,
            'admin_multimedia_objects' => $adminMultimediaObjects,
            'viewer_multimedia_objects' => $viewerMultimediaObjects,
            'countResources' => $countResources,
            'can_delete' => $canBeDeleted,
            'delete_group_message' => $deleteMessage,
        ];
    }

    /**
     * @Template("@PumukitNewAdmin/Group/dataresources.html.twig")
     */
    public function dataResourcesAction(Group $group, Request $request): array
    {
        $action = $request->get('action', '0');
        $resourceName = $request->get('resourceName', null);
        if (!$resourceName) {
            throw new \Exception('Missing resource name');
        }
        if ('user' === $resourceName) {
            $resources = $this->groupService->findUsersInGroup($group);
        } elseif ('multimediaobject' === $resourceName) {
            $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);
            $resources = $mmobjRepo->findWithGroup($group);
        } elseif ('embeddedbroadcast' === $resourceName) {
            $mmobjRepo = $this->documentManager->getRepository(MultimediaObject::class);
            $resources = $mmobjRepo->findWithGroupInEmbeddedBroadcast($group);
        } else {
            throw new \Exception('Invalid resource name');
        }

        return [
            'group' => $group,
            'action' => $action,
            'resources' => $resources,
            'resource_name' => $resourceName,
        ];
    }

    /**
     * @ParamConverter("user", class="PumukitSchemaBundle:User", options={"id" = "userId"})
     */
    public function deleteUserAction(User $user, Request $request)
    {
        $action = $request->get('action', '0');
        $group = $this->findOr404($request);
        $user = $this->userService->deleteGroup($group, $user);

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_data_resources', ['id' => $group->getId(), 'resourceName' => 'user', 'action' => $action]));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function deleteMultimediaObjectAction(MultimediaObject $multimediaObject, Request $request)
    {
        $action = $request->get('action', '0');
        $group = $this->findOr404($request);
        $this->multimediaObjectService->deleteGroup($group, $multimediaObject);

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_data_resources', ['id' => $group->getId(), 'resourceName' => 'multimediaobject', 'action' => $action]));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function deleteEmbeddedBroadcastAction(MultimediaObject $multimediaObject, Request $request)
    {
        $action = $request->get('action', '0');
        $group = $this->findOr404($request);
        $this->embeddedBroadcastService->deleteGroup($group, $multimediaObject);

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_data_resources', ['id' => $group->getId(), 'resourceName' => 'embeddedbroadcast', 'action' => $action]));
    }

    public function canBeDeletedAction(Group $group, Request $request)
    {
        try {
            $canBeDeleted = $this->groupService->canBeDeleted($group);
            $value = $canBeDeleted ? 1 : 0;
            $locale = $request->getLocale();
            $deleteMessage = $this->groupService->getDeleteMessage($group, $locale);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'canbedeleted' => $value,
            'deleteMessage' => $deleteMessage,
            'groupName' => $group->getName(),
        ]);
    }

    public function deleteAllUsersAction(Group $group)
    {
        try {
            $this->userService->deleteAllFromGroup($group);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_data_resources', ['id' => $group->getId(), 'resourceName' => 'user']));
    }

    public function deleteAllMultimediaObjectsAction(Group $group)
    {
        try {
            $this->multimediaObjectService->deleteAllFromGroup($group);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_data_resources', ['id' => $group->getId(), 'resourceName' => 'multimediaobject']));
    }

    public function deleteAllEmbeddedBroadcastsAction(Group $group)
    {
        try {
            $this->embeddedBroadcastService->deleteAllFromGroup($group);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_data_resources', ['id' => $group->getId(), 'resourceName' => 'embeddedbroadcast']));
    }

    public function getCriteria($criteria)
    {
        $new_criteria = parent::getCriteria($criteria);
        if (isset($new_criteria['origin']) &&
            '/all/i' == (string) $new_criteria['origin']) {
            unset($new_criteria['origin']);
        }

        return $new_criteria;
    }

    public function createNew()
    {
        return new Group();
    }
}
