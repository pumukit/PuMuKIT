<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminController extends ResourceController implements NewAdminControllerInterface
{
    /** @var FactoryService */
    protected $factoryService;
    /** @var GroupService */
    protected $groupService;
    /** @var UserService */
    protected $userService;
    /** @var SessionInterface */
    protected $session;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        UserService $userService,
        SessionInterface $session,
        TranslatorInterface $translator
    ) {
        parent::__construct($documentManager, $paginationService);
        $this->factoryService = $factoryService;
        $this->groupService = $groupService;
        $this->userService = $userService;
        $this->session = $session;
        $this->translator = $translator;
    }

    /**
     * Overwrite to update the criteria with Regex, and save it in the session.
     */
    public function indexAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $resources = $this->getResources($request, $criteria);

        $pluralName = $this->getPluralResourceName();
        $resourceName = $this->getResourceName();

        return $this->render(
            'PumukitNewAdminBundle:'.ucfirst($resourceName).':index.html.twig',
            [$pluralName => $resources]
        );
    }

    /**
     * Create Action
     * Overwrite to return list and not index
     * and show toast message.
     *
     * @return JsonResponse|Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAction(Request $request)
    {
        $resourceName = $this->getResourceName();

        $resource = $this->createNew();
        $form = $this->getForm($resource, $request->getLocale());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->documentManager->persist($resource);
                $this->documentManager->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], 409);
            }

            if (null === $resource) {
                return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
        }

        return $this->render(
            'PumukitNewAdminBundle:'.ucfirst($resourceName).':create.html.twig',
            [
                $resourceName => $resource,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Update Action
     * Overwrite to return list and not index
     * and show toast message.
     *
     * @return JsonResponse|Response|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateAction(Request $request)
    {
        $resourceName = $this->getResourceName();

        $resource = $this->findOr404($request);
        $form = $this->getForm($resource, $request->getLocale());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            try {
                $this->documentManager->persist($resource);
                $this->documentManager->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
        }

        return $this->render(
            'PumukitNewAdminBundle:'.ucfirst($resourceName).':update.html.twig',
            [
                $resourceName => $resource,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Clone the given resource.
     */
    public function copyAction(Request $request)
    {
        $resource = $this->findOr404($request);

        $new_resource = $resource->cloneResource();

        $this->update($new_resource);

        $this->addFlash('success', 'copy');

        return $this->redirectToIndex();
    }

    /**
     * Overwrite to update the session.
     */
    public function showAction(Request $request)
    {
        $resourceName = $this->getResourceName();

        $data = $this->findOr404($request);

        return $this->render(
            'PumukitNewAdminBundle:'.ucfirst($resourceName).':show.html.twig',
            [$this->getResourceName() => $data]
        );
    }

    /**
     * Delete action.
     */
    public function deleteAction(Request $request)
    {
        $resource = $this->findOr404($request);
        $resourceId = $resource->getId();
        $resourceName = $this->getResourceName();

        $this->factoryService->deleteResource($resource);
        if ($resourceId === $this->session->get('admin/'.$resourceName.'/id')) {
            $this->session->remove('admin/'.$resourceName.'/id');
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
    }

    /**
     * List action.
     */
    public function listAction(Request $request)
    {
        $pluralName = $this->getPluralResourceName();
        $resourceName = $this->getResourceName();

        $criteria = $this->getCriteria($request->get('criteria', []));
        $resources = $this->getResources($request, $criteria);

        return $this->render(
            'PumukitNewAdminBundle:'.ucfirst($resourceName).':list.html.twig',
            [$pluralName => $resources]
        );
    }

    /**
     * Overwrite to update the session.
     *
     * @param mixed $resource
     */
    public function delete($resource)
    {
        $this->session->remove('admin/'.$this->getResourceName().'/id');

        $this->factoryService->deleteResource($resource);
        $this->documentManager->flush();
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

    public function find($id)
    {
        $repository = $this->getRepository();

        $criteria = ['id' => $id];

        return $repository->findOneBy($criteria);
    }

    public function getCriteria($criteria)
    {
        if (array_key_exists('reset', $criteria)) {
            $this->session->remove('admin/'.$this->getResourceName().'/criteria');
        } elseif ($criteria) {
            $this->session->set('admin/'.$this->getResourceName().'/criteria', $criteria);
        }
        $criteria = $this->session->get('admin/'.$this->getResourceName().'/criteria', []);

        $new_criteria = [];
        foreach ($criteria as $property => $value) {
            //preg_match('/^\/.*?\/[imxlsu]*$/i', $e)
            if ('' !== $value) {
                $new_criteria[$property] = new Regex($value, 'i');
            }
        }

        return $new_criteria;
    }

    public function getResources(Request $request, $criteria)
    {
        $sorting = $this->getSorting($request);

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

    /**
     * Overwrite to get form with translations.
     *
     * @param string|null $resource
     * @param string      $locale
     *
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    public function getForm($resource = null, $locale = 'en')
    {
        $resourceName = $this->getResourceName();
        $formType = 'Pumukit\\NewAdminBundle\\Form\\Type\\'.ucfirst($resourceName).'Type';

        return $this->createForm($formType, $resource, ['translator' => $this->translator, 'locale' => $locale]);
    }

    /**
     * Get all groups for logged in user
     * according to user scope.
     *
     * @return mixed
     */
    public function getAllGroups()
    {
        $loggedInUser = $this->getUser();
        if ($loggedInUser->isSuperAdmin() || $this->userService->hasGlobalScope($loggedInUser)) {
            $allGroups = $this->groupService->findAll();
        } else {
            $allGroups = $loggedInUser->getGroups();
        }

        return $allGroups;
    }
}
