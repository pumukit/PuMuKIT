<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\NewAdminBundle\Form\Type\PermissionProfileType;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PermissionProfileEventDispatcherService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PermissionService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_PERMISSION_PROFILES')")
 */
class PermissionProfileController extends AdminController
{
    public static $resourceName = 'permissionprofile';
    public static $repoName = PermissionProfile::class;

    /** @var PermissionProfileService */
    protected $permissionProfileService;
    /** @var TranslatorInterface */
    private $translator;
    /** @var PermissionService */
    private $permissionService;
    /** @var SessionInterface */
    private $session;

    /** @var PermissionProfileEventDispatcherService */
    private $pumukitSchemaPermissionProfileDispatcher;
    private $pumukitUseSeriesChannels;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        UserService $userService,
        PermissionProfileService $permissionProfileService,
        TranslatorInterface $translator,
        PermissionService $permissionService,
        SessionInterface $session,
        PermissionProfileEventDispatcherService $pumukitSchemaPermissionProfileDispatcher,
        $pumukitUseSeriesChannels
    ) {
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService, $session);
        $this->permissionProfileService = $permissionProfileService;
        $this->translator = $translator;
        $this->permissionService = $permissionService;
        $this->session = $session;
        $this->pumukitUseSeriesChannels = $pumukitUseSeriesChannels;
        $this->pumukitSchemaPermissionProfileDispatcher = $pumukitSchemaPermissionProfileDispatcher;
    }

    /**
     * @Template("PumukitNewAdminBundle:PermissionProfile:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $permissionProfiles = $this->getResources($request, $criteria);

        [$permissions, $dependencies] = $this->getPermissions();
        $scopes = PermissionProfile::$scopeDescription;

        return [
            'permissionprofiles' => $permissionProfiles,
            'permissions' => $permissions,
            'scopes' => $scopes,
            'dependencies' => $dependencies,
        ];
    }

    /**
     * @Template("PumukitNewAdminBundle:PermissionProfile:list.html.twig")
     */
    public function listAction(Request $request)
    {
        $session = $this->session;

        $criteria = $this->getCriteria($request->get('criteria', []));
        $permissionProfiles = $this->getResources($request, $criteria);

        $page = $session->get('admin/permissionprofile/page', 1);
        $maxPerPage = $session->get('admin/permissionprofile/paginate', 9);
        $newPermissionProfileId = $request->get('id');
        if ($newPermissionProfileId && (($permissionProfiles->getNbResults() / $maxPerPage) > $page)) {
            $page = $permissionProfiles->getNbPages();
            $session->set('admin/permissionprofile/page', $page);
        }
        $permissionProfiles->setCurrentPage($page);

        [$permissions, $dependencies] = $this->getPermissions();
        $scopes = PermissionProfile::$scopeDescription;

        return [
            'permissionprofiles' => $permissionProfiles,
            'permissions' => $permissions,
            'scopes' => $scopes,
            'dependencies' => $dependencies,
        ];
    }

    /**
     * @Template("PumukitNewAdminBundle:PermissionProfile:create.html.twig")
     */
    public function createAction(Request $request)
    {
        $permissionProfile = new PermissionProfile();
        $form = $this->getForm($permissionProfile, $request->getLocale());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $permissionProfile = $this->permissionProfileService->update($permissionProfile, true);
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], 409);
            }
            if (null === $permissionProfile) {
                return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list', ['id' => $permissionProfile->getId()]));
        }

        return [
            'permissionprofile' => $permissionProfile,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Template("PumukitNewAdminBundle:PermissionProfile:update.html.twig")
     */
    public function updateAction(Request $request)
    {
        $permissionProfile = $this->findOr404($request);
        $form = $this->getForm($permissionProfile, $request->getLocale());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            try {
                $this->permissionProfileService->update($permissionProfile);
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
        }

        return [
            'permissionprofile' => $permissionProfile,
            'form' => $form->createView(),
        ];
    }

    public function getForm($permissionProfile = null, $locale = 'en')
    {
        return $this->createForm(PermissionProfileType::class, $permissionProfile, ['translator' => $this->translator, 'locale' => $locale]);
    }

    public function deleteAction(Request $request)
    {
        $permissionProfile = $this->findOr404($request);
        $permissionProfileId = $permissionProfile->getId();

        $response = $this->isAllowedToBeDeleted($permissionProfile);
        if ($response instanceof Response) {
            return $response;
        }

        try {
            $this->factoryService->deleteResource($permissionProfile);
            $this->pumukitSchemaPermissionProfileDispatcher->dispatchDelete($permissionProfile);
            if ($permissionProfileId === $this->session->get('admin/permissionprofile/id')) {
                $this->session->remove('admin/permissionprofile/id');
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
    }

    public function batchUpdateAction(Request $request)
    {
        $repo = $this->documentManager->getRepository(PermissionProfile::class);

        $selectedDefault = $request->get('selected_default');
        $selectedScopes = $request->get('selected_scopes');
        $checkedPermissions = $request->get('checked_permissions');

        if ('string' === gettype($selectedScopes)) {
            $selectedScopes = json_decode($selectedScopes, true);
        }
        if ('string' === gettype($checkedPermissions)) {
            $checkedPermissions = json_decode($checkedPermissions, true);
        }

        $newDefaultPermissionProfile = $this->find($selectedDefault);
        if (null !== $newDefaultPermissionProfile) {
            if (!$newDefaultPermissionProfile->isDefault()) {
                $newDefaultPermissionProfile->setDefault(true);
                $newDefaultPermissionProfile = $this->permissionProfileService->update($newDefaultPermissionProfile);
            }
        }

        $allPermissionProfiles = $this->isGranted('ROLE_SUPER_ADMIN') ? $repo->findAll() : $repo->findBySystem(false);

        //Doing a batch update for all checked profiles. This will remove everything except the checked permissions.
        $permissionProfiles = $this->buildPermissionProfiles($checkedPermissions, $selectedScopes);
        foreach ($permissionProfiles as $profileId => $p) {
            $permissionProfile = $this->findPermissionProfile($allPermissionProfiles, $profileId);
            if (null === $permissionProfile) {
                continue;
            }

            try {
                $permissionProfile = $this->permissionProfileService->setScope($permissionProfile, $p['scope'], false);
                $this->permissionProfileService->batchUpdate($permissionProfile, $p['permissions'], false);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }
        $this->documentManager->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
    }

    public function getResources(Request $request, $criteria)
    {
        $sorting = $this->getSorting();
        if (!isset($sorting['rank'])) {
            $sorting['rank'] = 1;
        }
        $session = $this->session;
        $session_namespace = 'admin/permissionprofile';

        $resources = $this->createPager($criteria, $sorting);

        if ($request->get('page', null)) {
            $session->set($session_namespace.'/page', $request->get('page', 1));
        }

        if ($request->get('paginate', null)) {
            $session->set($session_namespace.'/paginate', $request->get('paginate', 9));
        }

        $resources
            ->setMaxPerPage($session->get($session_namespace.'/paginate', 9))
            ->setNormalizeOutOfRangePages(true)
            ->setCurrentPage($session->get($session_namespace.'/page', 1))
        ;

        return $resources;
    }

    public function exportPermissionProfilesAction(): Response
    {
        return new Response(
            $this->permissionProfileService->exportAllToCsv(),
            Response::HTTP_OK,
            [
                'Content-Disposition' => 'attachment; filename="permission_profiles.csv"',
            ]
        );
    }

    private function buildPermissionProfiles($checkedPermissions, $selectedScopes): array
    {
        $permissionProfiles = [];
        //Adds scope and checked permissions to permissions.
        foreach ($checkedPermissions as $permission) {
            $data = $this->separateAttributePermissionProfilesIds($permission);
            $permissionProfiles[$data['profileId']]['permissions'][] = $data['attribute'];
        }
        foreach ($selectedScopes as $selectedScope) {
            $data = $this->separateAttributePermissionProfilesIds($selectedScope);
            if (isset($permissionProfiles[$data['profileId']])) {
                $permissionProfiles[$data['profileId']]['scope'] = $data['attribute'];
            } else {
                $permissionProfiles[$data['profileId']] = [
                    'permissions' => [],
                    'scope' => $data['attribute'],
                ];
            }
        }

        return $permissionProfiles;
    }

    private function separateAttributePermissionProfilesIds($pair = ''): array
    {
        $data = ['attribute' => '', 'profileId' => ''];
        if ($pair) {
            $output = explode('__', $pair);
            if (array_key_exists(0, $output)) {
                $data['attribute'] = $output[0];
            }
            if (array_key_exists(1, $output)) {
                $data['profileId'] = $output[1];
            }
        }

        return $data;
    }

    private function findPermissionProfile($permissionProfiles, $id = '')
    {
        foreach ($permissionProfiles as $permissionProfile) {
            if ($id == $permissionProfile->getId()) {
                return $permissionProfile;
            }
        }

        return null;
    }

    private function isAllowedToBeDeleted(PermissionProfile $permissionProfile)
    {
        $usersWithPermissionProfile = $this->userService->countUsersWithPermissionProfile($permissionProfile);

        if (0 < $usersWithPermissionProfile) {
            return new Response('Can not delete this permission profile "'.$permissionProfile->getName().'". There are '.$usersWithPermissionProfile.' user(s) with this permission profile.', Response::HTTP_FORBIDDEN);
        }

        return true;
    }

    private function getPermissions(): array
    {
        $permissions = $this->permissionService->getAllPermissions();

        if (!$this->pumukitUseSeriesChannels) {
            unset($permissions[Permission::ACCESS_SERIES_TYPES]);
        }

        $dependencies = $this->permissionService->getAllDependencies();

        return [$permissions, $dependencies];
    }
}
