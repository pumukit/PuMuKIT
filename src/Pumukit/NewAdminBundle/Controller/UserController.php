<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\User;

/**
 * @Security("is_granted('ROLE_ACCESS_ADMIN_USERS')")
 */
class UserController extends AdminController
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

        $user = new User();
        $defaultPermissionProfile = $permissionProfileService->getDefault();
        if (null == $defaultPermissionProfile) {
            throw new \Exception('Unable to assign a Permission Profile to the new User. There is no default Permission Profile');
        }
        $user->setPermissionProfile($defaultPermissionProfile);
        $form = $this->getForm($user);

        if ($form->handleRequest($request)->isValid()) {
            try {
                $user = $this->get('pumukitschema.user')->create($user);
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
        $form = $this->getForm($user);

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
}
