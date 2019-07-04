<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\PermissionProfile;

class AdminController extends ResourceController implements NewAdminControllerInterface
{
    /**
     * Overwrite to update the criteria with MongoRegex, and save it in the session.
     */
    public function indexAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []));
        $resources = $this->getResources($request, $criteria);

        $pluralName = $this->getPluralResourceName();
        $resourceName = $this->getResourceName();

        return $this->render('PumukitNewAdminBundle:'.ucfirst($resourceName).':index.html.twig',
                             [$pluralName => $resources]
        );
    }

    /**
     * Create Action
     * Overwrite to return list and not index
     * and show toast message.
     *
     * @param Request $request
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $resourceName = $this->getResourceName();

        $resource = $this->createNew();
        $form = $this->getForm($resource, $request->getLocale());

        if ($form->handleRequest($request)->isValid()) {
            try {
                $dm->persist($resource);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], 409);
            }

            if (null === $resource) {
                return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
        }

        return $this->render('PumukitNewAdminBundle:'.ucfirst($resourceName).':create.html.twig',
                             [
                                 $resourceName => $resource,
                                 'form' => $form->createView(),
                             ]);
    }

    /**
     * Update Action
     * Overwrite to return list and not index
     * and show toast message.
     *
     * @param Request $request
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function updateAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $resourceName = $this->getResourceName();

        $resource = $this->findOr404($request);
        $form = $this->getForm($resource, $request->getLocale());

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH']) && $form->handleRequest($request)->isValid()) {
            try {
                $dm->persist($resource);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(['status' => $e->getMessage()], 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
        }

        return $this->render('PumukitNewAdminBundle:'.ucfirst($resourceName).':update.html.twig',
                             [
                                 $resourceName => $resource,
                                 'form' => $form->createView(),
                             ]);
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

        return $this->render('PumukitNewAdminBundle:'.ucfirst($resourceName).':show.html.twig',
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

        $this->get('pumukitschema.factory')->deleteResource($resource);
        if ($resourceId === $this->get('session')->get('admin/'.$resourceName.'/id')) {
            $this->get('session')->remove('admin/'.$resourceName.'/id');
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

        return $this->render('PumukitNewAdminBundle:'.ucfirst($resourceName).':list.html.twig',
                             [$pluralName => $resources]
        );
    }

    /**
     * Overwrite to update the session.
     */
    public function delete($resource)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $this->get('session')->remove('admin/'.$this->getResourceName().'/id');

        $factory = $this->get('pumukitschema.factory');
        $factory->deleteResource($resource);
        $dm->flush();
    }

    public function batchDeleteAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $resourceName = $this->getResourceName();

        $factory = $this->get('pumukitschema.factory');
        foreach ($ids as $id) {
            $resource = $this->find($id);
            try {
                $factory->deleteResource($resource);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
            if ($id === $this->get('session')->get('admin/'.$resourceName.'/id')) {
                $this->get('session')->remove('admin/'.$resourceName.'/id');
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

    /**
     * Gets the criteria values.
     */
    public function getCriteria($criteria)
    {
        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/'.$this->getResourceName().'/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/'.$this->getResourceName().'/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/'.$this->getResourceName().'/criteria', []);

        $new_criteria = [];
        foreach ($criteria as $property => $value) {
            //preg_match('/^\/.*?\/[imxlsu]*$/i', $e)
            if ('' !== $value) {
                $new_criteria[$property] = new \MongoRegex('/'.$value.'/i');
            }
        }

        return $new_criteria;
    }

    /**
     * Gets the list of resources according to a criteria.
     */
    public function getResources(Request $request, $criteria)
    {
        $sorting = $this->getSorting($request);

        $session = $this->get('session');
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
            ->setCurrentPage($session->get($session_namespace.'/page', 1));

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

        $translator = $this->get('translator');

        $form = $this->createForm($formType, $resource, ['translator' => $translator, 'locale' => $locale]);

        return $form;
    }

    /**
     * Get all groups for logged in user
     * according to user scope.
     *
     * @return mixed
     */
    public function getAllGroups()
    {
        $groupService = $this->get('pumukitschema.group');
        $userService = $this->get('pumukitschema.user');
        $loggedInUser = $this->getUser();
        if ($loggedInUser->isSuperAdmin() || $userService->hasGlobalScope($loggedInUser)) {
            $allGroups = $groupService->findAll();
        } else {
            $allGroups = $loggedInUser->getGroups();
        }

        return $allGroups;
    }

    /**
     * @throws \Exception
     */
    public function exportRolesAction()
    {
        $languages = $this->getParameter('pumukit.locales');

        $csv = ['id', 'cod', 'xml', 'display'];
        foreach ($languages as $language) {
            $csv[] = 'name_'.$language;
        }

        foreach ($languages as $language) {
            $csv[] = 'text_'.$language;
        }

        $csv = implode(';', $csv);
        $csv = $csv.PHP_EOL;

        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $roles = $dm->getRepository(Role::class)->findAll();
        if (!$roles) {
            throw new \Exception('Not roles found');
        }

        $i = 1;
        foreach ($roles as $rol) {
            $dataCSV = [];
            $dataCSV[] = $i;
            $dataCSV[] = $rol->getCod();
            $dataCSV[] = $rol->getXML();
            $dataCSV[] = (int) $rol->getDisplay();
            foreach ($languages as $language) {
                $dataCSV[] = $rol->getName($language);
            }

            foreach ($languages as $language) {
                $dataCSV[] = $rol->getText($language);
            }

            $data = implode(';', $dataCSV);
            $csv .= $data.PHP_EOL;

            ++$i;
        }

        return new Response($csv, Response::HTTP_OK, ['Content-Disposition' => 'attachment; filename="roles_i18n.csv"']);
    }

    /**
     * @throws \Exception
     */
    public function exportPermissionProfilesAction()
    {
        $csv = ['id', 'name', 'system', 'default', 'scope', 'permissions'];
        $csv = implode(';', $csv);
        $csv = $csv.PHP_EOL;

        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $permissionProfiles = $dm->getRepository(PermissionProfile::class)->findAll();
        if (!$permissionProfiles) {
            throw new \Exception('Not permission profiles found');
        }

        $i = 1;
        foreach ($permissionProfiles as $pProfile) {
            $dataCSV = [];
            $dataCSV[] = $i;
            $dataCSV[] = $pProfile->getName();
            $dataCSV[] = (int) $pProfile->getSystem();
            $dataCSV[] = (int) $pProfile->getDefault();
            $dataCSV[] = $pProfile->getScope();

            $permission = [];
            foreach ($pProfile->getPermissions() as $permissionProfile) {
                $permission[] = $permissionProfile;
            }

            $dataPermission = implode(',', $permission);

            $dataCSV[] = $dataPermission;
            $data = implode(';', $dataCSV);
            $csv .= $data.PHP_EOL;

            ++$i;
        }

        return new Response($csv, Response::HTTP_OK, ['Content-Disposition' => 'attachment; filename="permissionprofiles.csv"']);
    }
}
