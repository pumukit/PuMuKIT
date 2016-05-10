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

        return array('users' => $users, 'profiles' => $profiles);
    }

    /**
     * Create Action
     * Overwrite to create Person
     * referenced to User.
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $config = $this->getConfiguration();
        $permissionProfileService = $this->get('pumukitschema.permissionprofile');
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

        return $this->render('PumukitNewAdminBundle:User:create.html.twig',
                             array(
                                   'user' => $user,
                                   'form' => $form->createView(),
                                   ));
    }

    /**
     * Update Action
     * Overwrite to update it with user manager
     * Checks plain password and updates encoded password.
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request)
    {
        $config = $this->getConfiguration();

        $userManager = $this->get('fos_user.user_manager');

        $user = $this->findOr404($request);
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $form = $this->createForm(new UserUpdateType($translator, $locale), $user);

        if (in_array($request->getMethod(), array('POST', 'PUT', 'PATCH')) && $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            try {
                $response = $this->isAllowedToBeUpdated($user);
                if ($response instanceof Response) {
                    return $response;
                }
                // false to not flush
                $userManager->updateUser($user, false);
                // To update aditional fields added
                $user = $this->get('pumukitschema.user')->update($user);
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

        return $this->render('PumukitNewAdminBundle:User:update.html.twig',
                             array(
                                   'user' => $user,
                                   'form' => $form->createView(),
                                   ));
    }

    /**
     * Delete action.
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
     */
    public function batchDeleteAction(Request $request)
    {
        $repo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:User');

        $ids = $this->getRequest()->get('ids');

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
     * Edit groups form
     * @Template("PumukitNewAdminBundle:User:editgroups.html.twig")
     */
    public function editGroupsAction(Request $request)
    {
        $user = $this->findOr404($request);
        if ($user->getOrigin() === User::ORIGIN_LDAP) {
            return new Response("Not allowed to update this user '".$user->getUsername()."' from LDAP", Response::HTTP_BAD_REQUEST);
        }
        $groups = $this->get('pumukitschema.group')->findAll();

        return array(
                     'user' => $user,
                     'groups' => $groups
                     );
    }

    /**
     * Update groups action
     */
    public function updateGroupsAction(Request $request)
    {
        $user = $this->findOr404($request);
        if ($user->getOrigin() === User::ORIGIN_LDAP) {
            return new Response("Not allowed to update this user '".$user->getUsername()."' from LDAP", Response::HTTP_BAD_REQUEST);
        }
        if ('POST' === $request->getMethod()){
            $addAdminGroups = $request->get('addAdminGroups', array());
            if ('string' === gettype($addAdminGroups)){
                $addAdminGroups = json_decode($addAdminGroups, true);
            }
            $addMemberGroups = $request->get('addMemberGroups', array());
            if ('string' === gettype($addMemberGroups)){
                $addMemberGroups = json_decode($addMemberGroups, true);
            }
            $deleteAdminGroups = $request->get('deleteAdminGroups', array());
            if ('string' === gettype($deleteAdminGroups)){
                $deleteAdminGroups = json_decode($deleteAdminGroups, true);
            }
            $deleteMemberGroups = $request->get('deleteMemberGroups', array());
            if ('string' === gettype($deleteMemberGroups)){
                $deleteMemberGroups = json_decode($deleteMemberGroups, true);
            }

            $this->modifyUserGroups($user, $addAdminGroups, $addMemberGroups, $deleteAdminGroups, $deleteMemberGroups);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_user_list'));
    }

    /**
     * Get user groups
     */
    public function getGroupsAction(Request $request)
    {
        $user = $this->findOr404($request);
        $groupService = $this->get('pumukitschema.group');
        $addAdminGroups = array();
        $addMemberGroups = array();
        $addAdminGroupsIds = array();
        $addMemberGroupsIds = array();
        $deleteAdminGroups = array();
        $deleteMemberGroups = array();
        if ('GET' === $request->getMethod()){
            foreach ($user->getAdminGroups() as $group) {
                $addAdminGroups[$group->getId()] = array(
                                                         'key' => $group->getKey(),
                                                         'name' => $group->getName(),
                                                         'origin' => $group->getOrigin()
                                                         );
                $addAdminGroupsIds[] = new \MongoId($group->getId());
            }
            foreach ($user->getMemberGroups() as $group) {
                $addMemberGroups[$group->getId()] = array(
                                                          'key' => $group->getKey(),
                                                          'name' => $group->getName(),
                                                          'origin' => $group->getOrigin()
                                                          );
                $addMemberGroupsIds[] = new \MongoId($group->getId());
            }
            $adminGroupsToDelete = $groupService->findByIdNotIn($addAdminGroupsIds);
            $memberGroupsToDelete = $groupService->findByIdNotIn($addMemberGroupsIds);
            foreach ($adminGroupsToDelete as $group) {
                $deleteAdminGroups[$group->getId()] = array(
                                                            'key' => $group->getKey(),
                                                            'name' => $group->getName(),
                                                            'origin' => $group->getOrigin()
                                                            );
            }
            foreach ($memberGroupsToDelete as $group) {
                $deleteMemberGroups[$group->getId()] = array(
                                                             'key' => $group->getKey(),
                                                             'name' => $group->getName(),
                                                             'origin' => $group->getOrigin()
                                                             );
            }
        }

        return new JsonResponse(array(
                                      'addAdmin' => $addAdminGroups,
                                      'addMember' => $addMemberGroups,
                                      'deleteAdmin' => $deleteAdminGroups,
                                      'deleteMember' => $deleteMemberGroups
                                      ));
    }

    /**
     * Modify User Groups
     */
    private function modifyUserGroups(User $user, $addAdminGroups = array(), $addMemberGroups = array(), $deleteAdminGroups = array(), $deleteMemberGroups = array())
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $groupRepo = $dm->getRepository('PumukitSchemaBundle:Group');
        $userService = $this->get('pumukitschema.user');

        foreach ($addAdminGroups as $addAdminGroup){
            $groupId = explode('_', $addAdminGroup)[2];
            $group = $groupRepo->find($groupId);
            if ($group) {
                $userService->addAdminGroup($group, $user, false);
            }
        }
        foreach ($addMemberGroups as $addMemberGroup){
            $groupId = explode('_', $addMemberGroup)[2];
            $group = $groupRepo->find($groupId);
            if ($group) {
                $userService->addMemberGroup($group, $user, false);
            }
        }
        foreach ($deleteAdminGroups as $deleteAdminGroup){
            $groupId = explode('_', $deleteAdminGroup)[2];
            $group = $groupRepo->find($groupId);
            if ($group) {
                $userService->deleteAdminGroup($group, $user, false);
            }
        }
        foreach ($deleteMemberGroups as $deleteMemberGroup){
            $groupId = explode('_', $deleteMemberGroup)[2];
            $group = $groupRepo->find($groupId);
            if ($group) {
                $userService->deleteMemberGroup($group, $user, false);
            }
        }

        $dm->flush();
    }

    private function isAllowedToBeDeleted(User $userToDelete)
    {
        $repo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:User');

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
                return new Response("Can not delete the user '".$userToDelete->getUsername()."'. ".$e->getMessage(), 409);
            }
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
        if ($userToUpdate->getOrigin() === User::ORIGIN_LDAP) {
            return new Response("Not allowed to update this user '".$userToUpdate->getUsername()."' from LDAP", Response::HTTP_BAD_REQUEST);
        }

        return true;
    }

    private function getNumberAdminUsers()
    {
        $repo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:User');

        return $repo->createQueryBuilder()
          ->where("function(){for ( var k in this.roles ) { if ( this.roles[k] == 'ROLE_SUPER_ADMIN' ) return true;}}")
          ->count()
          ->getQuery()
          ->execute();
    }

    private function getUniqueAdminUser()
    {
        $repo = $this
          ->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:User');

        return $repo->createQueryBuilder()
          ->where("function(){for ( var k in this.roles ) { if ( this.roles[k] == 'ROLE_SUPER_ADMIN' ) return true;}}")
          ->getQuery()
          ->getSingleResult();
    }

    /**
     * Gets the criteria values.
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
                if('all' != $value) {
                    $new_criteria[$property] = new \MongoId($value);
                }
            } elseif ('' !== $value) {
                $new_criteria[$property] = new \MongoRegex('/'.$value.'/i');
            }
        }

        return $new_criteria;
    }

    /**
     * Change the permission profiles of a list of users.
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
        foreach ($users as $user) {
            if (!$user->hasRole('ROLE_SUPER_ADMIN')) {
                $user->setPermissionProfile($profile);
                $user = $this->get('pumukitschema.user')->update($user);
            }
        }

        return new JsonResponse(array('ok'));
    }

    private function getGroupsToDelete($ids = array())
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $groupRepo = $dm->getRepository('PumukitSchemaBundle:Group');
        $groups = $groupRepo->createQueryBuilder()
            ->field('_id')->notIn($ids)
            ->getQuery()
            ->execute();
        return $groups;
    }
}
