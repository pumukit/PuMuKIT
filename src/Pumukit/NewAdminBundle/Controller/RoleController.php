<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\NewAdminBundle\Form\Type\RoleType;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\RoleService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Security("is_granted('ROLE_ACCESS_ROLES')")
 */
class RoleController extends SortableAdminController
{
    public static $resourceName = 'role';
    public static $repoName = Role::class;
    /** @var PersonService */
    protected $personService;
    /** @var TranslatorInterface */
    protected $translationService;
    /** @var RoleService */
    protected $roleService;
    /** @var SessionInterface */
    private $session;
    /** @var ValidatorInterface */
    private $validator;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        UserService $userService,
        PersonService $personService,
        TranslatorInterface $translationService,
        RoleService $roleService,
        SessionInterface $session,
        ValidatorInterface $validator
    ) {
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService);
        $this->personService = $personService;
        $this->translationService = $translationService;
        $this->roleService = $roleService;
        $this->session = $session;
        $this->validator = $validator;
    }

    /**
     * @Template("PumukitNewAdminBundle:Role:update.html.twig")
     */
    public function updateAction(Request $request)
    {
        $role = $this->personService->findRoleById($request->get('id'));

        $locale = $request->getLocale();
        $form = $this->createForm(RoleType::class, $role, ['translator' => $this->translationService, 'locale' => $locale]);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->personService->updateRole($role);
                } catch (\Exception $e) {
                    return new JsonResponse(['status' => $e->getMessage()], 409);
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_role_list'));
            }
            $errors = $this->validator->validate($role);
            $textStatus = '';
            foreach ($errors as $error) {
                $textStatus .= $error->getPropertyPath().' value '.$error->getInvalidValue().': '.$error->getMessage().'. ';
            }

            return new Response($textStatus, 409);
        }

        return [
            'role' => $role,
            'form' => $form->createView(),
        ];
    }

    public function getResources(Request $request, $criteria)
    {
        $sorting = $this->getSorting($request);
        $sorting['rank'] = 'asc';
        $session = $this->session;
        $session_namespace = 'admin/'.$this->getResourceName();

        $resources = $this->createPager($criteria, $sorting);

        if ($request->get('page', null)) {
            $session->set($session_namespace.'/page', $request->get('page', 1));
        }

        if ($request->get('paginate', null)) {
            $session->set($session_namespace.'/paginate', $request->get('paginate', 10));
        }

        $resources
            ->setMaxPerPage($session->get($session_namespace.'/paginate', 10))
            ->setNormalizeOutOfRangePages(true)
            ->setCurrentPage($session->get($session_namespace.'/page', 1))
        ;

        return $resources;
    }


    public function deleteAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $resourceId = $resource->getId();
        $resourceName = $this->getResourceName();

        if (0 !== $resource->getNumberPeopleInMultimediaObject()) {
            return new Response("Can not delete role '".$resource->getName()."', There are Multimedia objects with this role. ", 409);
        }

        $this->factoryService->deleteResource($resource);
        if ($resourceId === $this->session->get('admin/'.$resourceName.'/id')) {
            $this->session->remove('admin/'.$resourceName.'/id');
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
    }

    public function batchDeleteAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $resourceName = $this->getResourceName();

        foreach ($ids as $id) {
            $resource = $this->find($id);
            if (0 !== $resource->getNumberPeopleInMultimediaObject()) {
                continue;
            }

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
        return new Role();
    }

    public function exportRolesAction(): Response
    {
        return new Response(
            $this->roleService->exportAllToCsv(),
            Response::HTTP_OK,
            [
                'Content-Disposition' => 'attachment; filename="roles_i18n.csv"',
            ]
        );
    }
}
