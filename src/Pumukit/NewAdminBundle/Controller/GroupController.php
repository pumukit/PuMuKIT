<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Security("is_granted('ROLE_ACCESS_GROUPS')")
 */
class GroupController extends AdminController implements NewAdminControllerInterface
{
    public static $resourceName = 'group';
    public static $repoName = Group::class;

    /**
     * Index.
     *
     * @Template("PumukitNewAdminBundle:Group:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $groups = $this->getResources($request, $criteria);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $origins = $dm
            ->createQueryBuilder(Group::class)
            ->distinct('origin')
            ->getQuery()
            ->execute()
        ;

        return ['groups' => $groups, 'origins' => $origins->toArray()];
    }

    /**
     * List action.
     *
     * @Template("PumukitNewAdminBundle:Group:list.html.twig")
     */
    public function listAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $groups = $this->getResources($request, $criteria);

        return ['groups' => $groups];
    }

    /**
     * Create Action
     * Overwrite to use group service
     * to check if exists and dispatch event.
     *
     * @param Request $request
     *
     * @return JsonResponse|Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAction(Request $request)
    {
        $group = $this->createNew();
        $form = $this->getForm($group, $request->getLocale());

        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            $formHandleRequest = $form->handleRequest($request);
            if ($formHandleRequest->isValid()) {
                try {
                    $group = $this->get('pumukitschema.group')->create($group);
                } catch (\Exception $e) {
                    return new JsonResponse([$e->getMessage()], Response::HTTP_BAD_REQUEST);
                }

                if (null === $group) {
                    return $this->redirect($this->generateUrl('pumukitnewadmin_group_list'));
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_group_list'));
            }

            return new JsonResponse(['Form not valid'], Response::HTTP_BAD_REQUEST);
        }

        return $this->render(
            'PumukitNewAdminBundle:Group:create.html.twig',
            [
                'group' => $group,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Update Action
     * Overwrite to avoid updating not
     * local groups and to use group service
     * to update group and dispatch event.
     *
     * @param Request $request
     *
     * @return JsonResponse|Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateAction(Request $request)
    {
        $group = $this->findOr404($request);
        if (!$group->isLocal()) {
            return new Response('Not allowed to update not local Group', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $form = $this->getForm($group, $request->getLocale());

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH']) && $form->handleRequest($request)->isValid()) {
            try {
                $group = $this->get('pumukitschema.group')->update($group);
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_group_list'));
        }

        return $this->render(
            'PumukitNewAdminBundle:Group:update.html.twig',
            [
                'group' => $group,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Delete Group.
     *
     * @Template("PumukitNewAdminBundle:Group:list.html.twig")
     */
    public function deleteAction(Request $request)
    {
        $groupService = $this->get('pumukitschema.group');
        $group = $groupService->findById($request->get('id'));

        try {
            $groupService->delete($group);
        } catch (\Exception $e) {
            return new Response("Can not delete Group '".$group->getName()."'. ".$e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_list'));
    }

    /**
     * Batch delete Group
     * Overwrite to use GroupService.
     */
    public function batchDeleteAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $groupService = $this->get('pumukitschema.group');
        $translator = $this->get('translator');
        $notDeleted = [];
        foreach ($ids as $id) {
            $group = $groupService->findById($id);

            try {
                $groupService->delete($group);
            } catch (\Exception $e) {
                if (0 === strpos($e->getMessage(), 'Not allowed to delete')) {
                    $notDeleted[] = $group->getKey();
                } else {
                    return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
                }
            }
            if ($id === $this->get('session')->get('admin/group/id')) {
                $this->get('session')->remove('admin/group/id');
            }
        }
        if ($notDeleted) {
            $code = Response::HTTP_BAD_REQUEST;
            $message = $translator->trans('Not allowed to delete Groups:');
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
            $message = $translator->trans('Groups successfully deleted');
        }

        return new JsonResponse($message, $code);
    }

    /**
     * Gets the list of resources according to a criteria.
     *
     * @param mixed $criteria
     */
    public function getResources(Request $request, $criteria)
    {
        $sorting = $this->getSorting($request);
        $session = $this->get('session');
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

    /**
     * Get sorting for group.
     *
     * @param Request    $request
     * @param null|mixed $session_namespace
     *
     * @return array
     */
    public function getSorting(Request $request = null, $session_namespace = null)
    {
        $session = $this->get('session');
        if ($sorting = $request->get('sorting')) {
            $session->set('admin/group/type', $sorting[key($sorting)]);
            $session->set('admin/group/sort', key($sorting));
        }
        $value = $session->get('admin/group/type', 'asc');
        $key = $session->get('admin/group/sort', 'name');

        return [$key => $value];
    }

    /**
     * Info Action.
     *
     * @Template("PumukitNewAdminBundle:Group:info.html.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function infoAction(Request $request)
    {
        $group = $this->findOr404($request);
        $locale = $request->getLocale();
        $action = $request->get('action', false);
        $usersSort = ['username' => 1];
        $limit = 101;
        $users = $this->get('pumukitschema.group')->findUsersInGroup($group, $usersSort, $limit);
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $mmobjRepo = $dm->getRepository(MultimediaObject::class);
        if ($locale) {
            $sort = ['title.'.$locale => 1];
        } else {
            $sort = ['title' => 1];
        }
        $adminMultimediaObjects = $mmobjRepo->findWithGroup($group, $sort, $limit);
        $viewerMultimediaObjects = $mmobjRepo->findWithGroupInEmbeddedBroadcast($group, $sort, $limit);
        $groupService = $this->get('pumukitschema.group');
        $countResources = $groupService->countResourcesInGroup($group);
        $canBeDeleted = $groupService->canBeDeleted($group);
        $deleteMessage = $groupService->getDeleteMessage($group, $locale);

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
     * Data Resource Action.
     *
     * @Template("PumukitNewAdminBundle:Group:dataresources.html.twig")
     *
     * @param Group   $group
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return array
     */
    public function dataResourcesAction(Group $group, Request $request)
    {
        $action = $request->get('action', '0');
        $resourceName = $request->get('resourceName', null);
        if (!$resourceName) {
            throw new \Exception('Missing resource name');
        }
        if ('user' === $resourceName) {
            $resources = $this->get('pumukitschema.group')->findUsersInGroup($group);
        } elseif ('multimediaobject' === $resourceName) {
            $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $mmobjRepo = $dm->getRepository(MultimediaObject::class);
            $resources = $mmobjRepo->findWithGroup($group);
        } elseif ('embeddedbroadcast' === $resourceName) {
            $dm = $this->get('doctrine_mongodb.odm.document_manager');
            $mmobjRepo = $dm->getRepository(MultimediaObject::class);
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
     * Delete User from Group action.
     *
     * @ParamConverter("user", class="PumukitSchemaBundle:User", options={"id" = "userId"})
     */
    public function deleteUserAction(User $user, Request $request)
    {
        $action = $request->get('action', '0');
        $group = $this->findOr404($request);
        $user = $this->get('pumukitschema.user')->deleteGroup($group, $user);

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_data_resources', ['id' => $group->getId(), 'resourceName' => 'user', 'action' => $action]));
    }

    /**
     * Delete MultimediaObject from Group action.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function deleteMultimediaObjectAction(MultimediaObject $multimediaObject, Request $request)
    {
        $action = $request->get('action', '0');
        $group = $this->findOr404($request);
        $multimediaobject = $this->get('pumukitschema.multimedia_object')->deleteGroup($group, $multimediaObject);

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_data_resources', ['id' => $group->getId(), 'resourceName' => 'multimediaobject', 'action' => $action]));
    }

    /**
     * Delete Embeddedbroadcast from Group action.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function deleteEmbeddedBroadcastAction(MultimediaObject $multimediaObject, Request $request)
    {
        $action = $request->get('action', '0');
        $group = $this->findOr404($request);
        $multimediaobject = $this->get('pumukitschema.embeddedbroadcast')->deleteGroup($group, $multimediaObject);

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_data_resources', ['id' => $group->getId(), 'resourceName' => 'embeddedbroadcast', 'action' => $action]));
    }

    /**
     * Can be deleted.
     *
     * @param Group   $group
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function canBeDeletedAction(Group $group, Request $request)
    {
        try {
            $groupService = $this->get('pumukitschema.group');
            $canBeDeleted = $groupService->canBeDeleted($group);
            $value = $canBeDeleted ? 1 : 0;
            $locale = $request->getLocale();
            $deleteMessage = $groupService->getDeleteMessage($group, $locale);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'canbedeleted' => $value,
            'deleteMessage' => $deleteMessage,
            'groupName' => $group->getName(),
        ]);
    }

    /**
     * Delete all users from group.
     *
     * @param Group   $group
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAllUsersAction(Group $group, Request $request)
    {
        try {
            $userService = $this->get('pumukitschema.user');
            $userService->deleteAllFromGroup($group);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_data_resources', ['id' => $group->getId(), 'resourceName' => 'user']));
    }

    /**
     * Delete all multimediaObjects from group.
     *
     * @param Group   $group
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAllMultimediaObjectsAction(Group $group, Request $request)
    {
        try {
            $mmsService = $this->get('pumukitschema.multimedia_object');
            $mmsService->deleteAllFromGroup($group);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_group_data_resources', ['id' => $group->getId(), 'resourceName' => 'multimediaobject']));
    }

    /**
     * Delete all embeddedbroadcasts from group.
     *
     * @param Group   $group
     * @param Request $request
     *
     * @return Response
     */
    public function deleteAllEmbeddedBroadcastsAction(Group $group, Request $request)
    {
        try {
            $embeddedBroadcastService = $this->get('pumukitschema.embeddedbroadcast');
            $embeddedBroadcastService->deleteAllFromGroup($group);
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
