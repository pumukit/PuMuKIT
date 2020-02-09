<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use FOS\UserBundle\Model\UserManagerInterface;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\NewAdminBundle\Form\Type\UserUpdateType;
use Pumukit\SchemaBundle\Document\Group;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_ADMIN_USERS')")
 */
class UserController extends AdminController
{
    public static $resourceName = 'user';
    public static $repoName = User::class;

    /** @var DocumentManager  */
    private $documentManager;
    /** @var PaginationService  */
    private $paginationService;
    /** @var FactoryService  */
    private $factoryService;
    /** @var GroupService  */
    private $groupService;
    /** @var UserService  */
    private $userService;
    /** @var PersonService  */
    private $personService;
    /** @var TranslatorInterface */
    private $translator;
    /** @var SessionInterface */
    private $session;
    /** @var UserManagerInterface */
    private $fosUserUserManager;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        UserService $userService,
        PersonService $personService,
        TranslatorInterface $translator,
        SessionInterface $session,
        UserManagerInterface $fosUserUserManager
    ) {
        $this->documentManager = $documentManager;
        $this->groupService = $groupService;
        $this->userService = $userService;
        $this->personService = $personService;
        $this->translator = $translator;
        $this->session = $session;
        $this->fosUserUserManager = $fosUserUserManager;
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService);
    }

    /**
     * @Template("PumukitNewAdminBundle:User:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $users = $this->getResources($request, $criteria);
        $repo = $this->documentManager->getRepository(PermissionProfile::class);
        $profiles = $repo->findAll();

        $origins = $this->documentManager->createQueryBuilder(User::class)->distinct('origin')->getQuery()->execute();

        return ['users' => $users, 'profiles' => $profiles, 'origins' => $origins->toArray()];
    }

    public function createAction(Request $request)
    {
        $user = $this->userService->instantiate();
        $form = $this->getForm($user, $request->getLocale());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $this->userService->create($user);
                $this->personService->referencePersonIntoUser($user);
            } catch (\Exception $e) {
                throw $e;
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_user_list'));
        }

        return $this->render(
            'PumukitNewAdminBundle:User:create.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }

    public function updateAction(Request $request)
    {
        $userManager = $this->fosUserUserManager;

        $user = $this->findOr404($request);

        $locale = $request->getLocale();
        $form = $this->createForm(UserUpdateType::class, $user, ['translator' => $this->translator, 'locale' => $locale]);

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    if (!$user->isLocal()) {
                        $user = $this->userService->update($user, true, false);
                    } else {
                        $response = $this->isAllowedToBeUpdated($user);
                        if ($response instanceof Response) {
                            return $response;
                        }
                        // false to not flush
                        $userManager->updateUser($user, false);
                        // To update aditional fields added
                        $this->userService->update($user);
                    }
                } catch (\Exception $e) {
                    throw $e;
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_user_list'));
            }
        }

        return $this->render(
            'PumukitNewAdminBundle:User:update.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }

    public function deleteAction(Request $request)
    {
        $userToDelete = $this->findOr404($request);

        $response = $this->isAllowedToBeDeleted($userToDelete);
        if ($response instanceof Response) {
            return $response;
        }

        return parent::deleteAction($request);
    }

    public function batchDeleteAction(Request $request)
    {
        $repo = $this->documentManager->getRepository(User::class);

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
     * @Template("PumukitNewAdminBundle:User:editgroups.html.twig")
     */
    public function editGroupsAction(Request $request)
    {
        $user = $this->findOr404($request);
        $groups = $this->groupService->findAll();

        return [
            'user' => $user,
            'groups' => $groups,
        ];
    }

    public function updateGroupsAction(Request $request)
    {
        $user = $this->findOr404($request);

        if ('POST' === $request->getMethod()) {
            $addGroups = $request->get('addGroups', []);
            if ('string' === gettype($addGroups)) {
                $addGroups = json_decode($addGroups, true);
            }
            $deleteGroups = $request->get('deleteGroups', []);
            if ('string' === gettype($deleteGroups)) {
                $deleteGroups = json_decode($deleteGroups, true);
            }

            $this->modifyUserGroups($user, $addGroups, $deleteGroups);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_user_list'));
    }

    public function getGroupsAction(Request $request)
    {
        $user = $this->findOr404($request);

        $addGroups = [];
        $addGroupsIds = [];
        $deleteGroups = [];
        if ('GET' === $request->getMethod()) {
            foreach ($user->getGroups() as $group) {
                $addGroups[$group->getId()] = [
                    'key' => $group->getKey(),
                    'name' => $group->getName(),
                    'origin' => $group->getOrigin(),
                ];
                $addGroupsIds[] = new ObjectId($group->getId());
            }
            $groupsToDelete = $this->groupService->findByIdNotIn($addGroupsIds);
            foreach ($groupsToDelete as $group) {
                $deleteGroups[$group->getId()] = [
                    'key' => $group->getKey(),
                    'name' => $group->getName(),
                    'origin' => $group->getOrigin(),
                ];
            }
        }

        return new JsonResponse(
            [
                'add' => $addGroups,
                'delete' => $deleteGroups,
                'userOrigin' => $user->getOrigin(),
            ]
        );
    }
    public function getCriteria($criteria)
    {
        if (array_key_exists('reset', $criteria)) {
            $this->session->remove('admin/user/criteria');
        } elseif ($criteria) {
            $this->session->set('admin/user/criteria', $criteria);
        }
        $criteria = $this->session->get('admin/user/criteria', []);

        $new_criteria = [];
        foreach ($criteria as $property => $value) {
            if ('permissionProfile' === $property) {
                if ('all' !== $value) {
                    $new_criteria[$property] = new ObjectId($value);
                }
            } elseif ('origin' === $property) {
                if ('all' !== $value) {
                    $new_criteria[$property] = $value;
                }
            } elseif ('' !== $value) {
                $new_criteria[$property] = new Regex($value, 'i');
            }
        }

        return $new_criteria;
    }

    public function promoteAction(Request $request)
    {
        $profileRepo = $this->documentManager->getRepository(PermissionProfile::class);
        $usersRepo = $this->documentManager->getRepository(User::class);

        $ids = $request->request->get('ids');
        $profile = $profileRepo->find($request->request->get('profile'));

        if (!$profile) {
            throw $this->createNotFoundException('Profile not found!');
        }

        $users = $usersRepo->findBy(['_id' => ['$in' => $ids]]);

        try {
            foreach ($users as $user) {
                if (!$user->hasRole('ROLE_SUPER_ADMIN')) {
                    $user->setPermissionProfile($profile);
                    $this->userService->update($user, true, false);
                }
            }
        } catch (\Exception $e) {
            throw $this->createAccessDeniedException('Unable to promote user');
        }

        return new JsonResponse(['ok']);
    }

    private function modifyUserGroups(User $user, array $addGroups = [], array $deleteGroups = [])
    {
        $groupRepo = $this->documentManager->getRepository(Group::class);

        foreach ($addGroups as $addGroup) {
            $groupsIds = explode('_', $addGroup);
            $groupId = $groupsIds[2];
            $group = $groupRepo->find($groupId);
            if ($group) {
                $this->userService->addGroup($group, $user, false);
            }
        }

        foreach ($deleteGroups as $deleteGroup) {
            $groupsIds = explode('_', $deleteGroup);
            $groupId = $groupsIds[2];
            $group = $groupRepo->find($groupId);
            if ($group) {
                $this->userService->deleteGroup($group, $user, false);
            }
        }

        $this->documentManager->flush();
    }

    private function isAllowedToBeDeleted(User $userToDelete)
    {
        $repo = $this->documentManager->getRepository(User::class);

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

        if (null !== $person = $userToDelete->getPerson()) {
            try {
                $this->personService->removeUserFromPerson($userToDelete, $person, true);
            } catch (\Exception $e) {
                return new Response(
                    "Can not delete the user '".$userToDelete->getUsername()."'. ".$e->getMessage(),
                    409
                );
            }
        }

        if (User::ORIGIN_LOCAL !== $userToDelete->getOrigin()) {
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
        $repo = $this->documentManager->getRepository(User::class);

        return $repo->createQueryBuilder()->where(
            "function(){for ( var k in this.roles ) { if ( this.roles[k] == 'ROLE_SUPER_ADMIN' ) return true;}}"
        )->count()->getQuery()->execute();
    }

    private function getUniqueAdminUser()
    {
        $repo = $this->documentManager->getRepository(User::class);

        return $repo->createQueryBuilder()->where(
            "function(){for ( var k in this.roles ) { if ( this.roles[k] == 'ROLE_SUPER_ADMIN' ) return true;}}"
        )->getQuery()->getSingleResult();
    }
}
