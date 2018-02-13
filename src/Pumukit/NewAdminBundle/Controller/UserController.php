<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\NewAdminBundle\Form\Type\UserUpdateType;

/**
 * @Security("is_granted('ROLE_ACCESS_ADMIN_USERS')")
 */
class UserController extends AdminController implements NewAdminController
{
    /**
     * Overwrite to check Users creation.
     *
     * @param Request $request
     *
     * @return array|Response
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $config = $this->getConfiguration();

        $criteria = $this->getCriteria($config);
        $users = $this->getResources($request, $config, $criteria);
        $repo = $dm->getRepository('PumukitSchemaBundle:PermissionProfile');
        $profiles = $repo->findAll();

        $origins = $dm->createQueryBuilder('PumukitSchemaBundle:User')->distinct('origin')->getQuery()->execute();

        return array('users' => $users, 'profiles' => $profiles, 'origins' => $origins->toArray());
    }

    /**
     * Create Action
     * Overwrite to create Person
     * referenced to User.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \Exception
     */
    public function createAction(Request $request)
    {
        $userService = $this->get('pumukitschema.user');

        $user = $userService->instantiate();
        $form = $this->getForm($user);

        if ($form->handleRequest($request)->isValid()) {
            try {
                $user = $userService->create($user);
                $user = $this->get('pumukitschema.person')->referencePersonIntoUser($user);
            } catch (\Exception $e) {
                throw $e;
            }
            if ($this->config->isApiRequest()) {
                return $this->handleView($this->view($user, 201));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_user_list'));
        }

        if ($this->config->isApiRequest()) {
            return $this->handleView($this->view($form));
        }

        return $this->render(
            'PumukitNewAdminBundle:User:create.html.twig',
            array(
                'user' => $user,
                'form' => $form->createView(),
            )
        );
    }

    /**
     * Update Action
     * Overwrite to update it with user manager
     * Checks plain password and updates encoded password.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \Exception
     */
    public function updateAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');

        $user = $this->findOr404($request);
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $form = $this->createForm(new UserUpdateType($translator, $locale), $user);

        if (in_array($request->getMethod(), array('POST', 'PUT', 'PATCH')) && $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            try {
                if ($this->isGranted('ROLE_ADMIN')) {
                    $user = $this->get('pumukitschema.user')->update($user, true, false);
                } else {
                    $response = $this->isAllowedToBeUpdated($user);
                    if ($response instanceof Response) {
                        return $response;
                    }
                    // false to not flush
                    $userManager->updateUser($user, false);
                    // To update aditional fields added
                    $user = $this->get('pumukitschema.user')->update($user);
                }
            } catch (\Exception $e) {
                throw $e;
            }
            if ($this->config->isApiRequest()) {
                return $this->handleView($this->view($user, 204));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_user_list'));
        }

        if ($this->config->isApiRequest()) {
            return $this->handleView($this->view($form));
        }

        return $this->render(
            'PumukitNewAdminBundle:User:update.html.twig',
            array(
                'user' => $user,
                'form' => $form->createView(),
            )
        );
    }

    /**
     * Delete action.
     *
     * @param Request $request
     *
     * @return bool|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function deleteAction(Request $request)
    {
        $userToDelete = $this->findOr404($request);

        $response = $this->isAllowedToBeDeleted($userToDelete);
        if ($response instanceof Response) {
            return $response;
        }

        return parent::deleteAction($request);
    }

    /**
     * Batch Delete action.
     *
     * @param Request $request
     *
     * @return bool|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    public function batchDeleteAction(Request $request)
    {
        $repo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:User');

        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        foreach ($ids as $id) {
            $userToDelete = $repo->find($id);
            $response = $this->isAllowedToBeDeleted($userToDelete);
            if ($response instanceof Response) {
                return $response;
            }
        }

        return parent::batchDeleteAction($request);
    }

    /**
     * Edit groups form.
     *
     * @param Request $request
     *
     * @return array
     * @Template("PumukitNewAdminBundle:User:editgroups.html.twig")
     */
    public function editGroupsAction(Request $request)
    {
        $user = $this->findOr404($request);
        $groups = $this->get('pumukitschema.group')->findAll();

        return array(
            'user' => $user,
            'groups' => $groups,
        );
    }

    /**
     * Update groups action.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @throws \Exception
     */
    public function updateGroupsAction(Request $request)
    {
        $user = $this->findOr404($request);

        /*if (!$user->isLocal()) {
            return new Response("Not allowed to update this not local user '".$user->getUsername()."'", Response::HTTP_BAD_REQUEST);
        }*/

        if ('POST' === $request->getMethod()) {
            $addGroups = $request->get('addGroups', array());
            if ('string' === gettype($addGroups)) {
                $addGroups = json_decode($addGroups, true);
            }
            $deleteGroups = $request->get('deleteGroups', array());
            if ('string' === gettype($deleteGroups)) {
                $deleteGroups = json_decode($deleteGroups, true);
            }

            $this->modifyUserGroups($user, $addGroups, $deleteGroups);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_user_list'));
    }

    /**
     * Get user groups.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getGroupsAction(Request $request)
    {
        $user = $this->findOr404($request);
        $groupService = $this->get('pumukitschema.group');
        $addGroups = array();
        $addGroupsIds = array();
        $deleteGroups = array();
        if ('GET' === $request->getMethod()) {
            foreach ($user->getGroups() as $group) {
                $addGroups[$group->getId()] = array(
                    'key' => $group->getKey(),
                    'name' => $group->getName(),
                    'origin' => $group->getOrigin(),
                );
                $addGroupsIds[] = new \MongoId($group->getId());
            }
            $groupsToDelete = $groupService->findByIdNotIn($addGroupsIds);
            foreach ($groupsToDelete as $group) {
                $deleteGroups[$group->getId()] = array(
                    'key' => $group->getKey(),
                    'name' => $group->getName(),
                    'origin' => $group->getOrigin(),
                );
            }
        }

        return new JsonResponse(
            array(
                'add' => $addGroups,
                'delete' => $deleteGroups,
                'userOrigin' => $user->getOrigin(),
            )
        );
    }

    /**
     * Modify User Groups.
     *
     * @param User  $user
     * @param array $addGroups
     * @param array $deleteGroups
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @throws \Exception
     */
    private function modifyUserGroups(User $user, $addGroups = array(), $deleteGroups = array())
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $groupRepo = $dm->getRepository('PumukitSchemaBundle:Group');
        $userService = $this->get('pumukitschema.user');

        foreach ($addGroups as $addGroup) {
            $groupsIds = explode('_', $addGroup);
            $groupId = $groupsIds[2];
            $group = $groupRepo->find($groupId);
            if ($group) {
                $userService->addGroup($group, $user, false);
            }
        }

        foreach ($deleteGroups as $deleteGroup) {
            $groupsIds = explode('_', $deleteGroup);
            $groupId = $groupsIds[2];
            $group = $groupRepo->find($groupId);
            if ($group) {
                $userService->deleteGroup($group, $user, false);
            }
        }

        $dm->flush();
    }

    private function isAllowedToBeDeleted(User $userToDelete)
    {
        $repo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:User');

        $loggedInUser = $this->getUser();

        if ($loggedInUser === $userToDelete) {
            return new Response("Can not delete the logged in user '".$loggedInUser->getUsername()."'", 409);
        }
        if (1 === $repo->createQueryBuilder()->getQuery()->execute()->count()) {
            return new Response("Can not delete this unique user '".$userToDelete->getUsername()."'", 409);
        }

        $numberAdminUsers = $this->getNumberAdminUsers();

        if ((1 === $numberAdminUsers) && ($userToDelete->isSuperAdmin())) {
            return new Response("Can not delete this unique admin user '".$userToDelete->getUsername()."'", 409);
        }

        if (null != $person = $userToDelete->getPerson()) {
            try {
                $this->get('pumukitschema.person')->removeUserFromPerson($userToDelete, $person, true);
            } catch (\Exception $e) {
                return new Response(
                    "Can not delete the user '".$userToDelete->getUsername()."'. ".$e->getMessage(), 409
                );
            }
        }

        if ($userToDelete->getOrigin() !== User::ORIGIN_LOCAL) {
            if ($loggedInUser->isSuperAdmin()) {
                return true;
            }

            return new Response("Can not delete the external user '".$userToDelete->getUsername()."'. ", 409);
        }

        return true;
    }

    private function isAllowedToBeUpdated(User $userToUpdate)
    {
        $numberAdminUsers = $this->getNumberAdminUsers();

        if ((1 === $numberAdminUsers)) {
            if (($userToUpdate === $this->getUniqueAdminUser()) && (!$userToUpdate->isSuperAdmin())) {
                return new Response("Can not update this unique admin user '".$userToUpdate->getUsername()."'", 409);
            }
        }
        if (!$userToUpdate->isLocal()) {
            return new Response(
                "Not allowed to update this not local user '".$userToUpdate->getUsername()."'",
                Response::HTTP_BAD_REQUEST
            );
        }

        return true;
    }

    private function getNumberAdminUsers()
    {
        $repo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:User');

        return $repo->createQueryBuilder()->where(
                "function(){for ( var k in this.roles ) { if ( this.roles[k] == 'ROLE_SUPER_ADMIN' ) return true;}}"
            )->count()->getQuery()->execute();
    }

    private function getUniqueAdminUser()
    {
        $repo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:User');

        return $repo->createQueryBuilder()->where(
                "function(){for ( var k in this.roles ) { if ( this.roles[k] == 'ROLE_SUPER_ADMIN' ) return true;}}"
            )->getQuery()->getSingleResult();
    }

    /**
     * Gets the criteria values.
     *
     * @param $config
     *
     * @return array
     */
    public function getCriteria($config)
    {
        $criteria = $config->getCriteria();

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/user/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/user/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/user/criteria', array());

        $new_criteria = array();
        foreach ($criteria as $property => $value) {
            if ('permissionProfile' == $property) {
                if ('all' != $value) {
                    $new_criteria[$property] = new \MongoId($value);
                }
            } elseif ('origin' == $property) {
                if ('all' != $value) {
                    $new_criteria[$property] = $value;
                }
            } elseif ('' !== $value) {
                $new_criteria[$property] = new \MongoRegex('/'.$value.'/i');
            }
        }

        return $new_criteria;
    }

    /**
     * Change the permission profiles of a list of users.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function promoteAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $profileRepo = $dm->getRepository('PumukitSchemaBundle:PermissionProfile');
        $usersRepo = $dm->getRepository('PumukitSchemaBundle:User');

        $ids = $request->request->get('ids');
        $profile = $profileRepo->find($request->request->get('profile'));

        if (!$profile) {
            throw $this->createNotFoundException('Profile not found!');
        }

        $users = $usersRepo->findBy(array('_id' => array('$in' => $ids)));

        $checkOrigin = !$this->isGranted('ROLE_ADMIN');

        try {
            foreach ($users as $user) {
                if (!$user->hasRole('ROLE_SUPER_ADMIN')) {
                    $user->setPermissionProfile($profile);
                    $this->get('pumukitschema.user')->update($user, true, $checkOrigin);
                }
            }
        } catch (\Exception $e) {
            throw $this->createAccessDeniedException('Unable to promote user');
        }

        return new JsonResponse(array('ok'));
    }
}
