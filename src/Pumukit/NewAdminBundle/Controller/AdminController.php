<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminController extends ResourceController implements NewAdminController
{
    /**
     * Overwrite to update the criteria with MongoRegex, and save it in the session.
     */
    public function indexAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', array()));
        $resources = $this->getResources($request, $criteria);

        $pluralName = $this->getPluralResourceName();
        $resourceName = $this->getResourceName();

        return $this->render('PumukitNewAdminBundle:'.ucfirst($resourceName).':index.html.twig',
                             array($pluralName => $resources)
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
        $form = $this->getForm($resource);

        if ($form->handleRequest($request)->isValid()) {
            try {
                $dm->persist($resource);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), 409);
            }

            if (null === $resource) {
                return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
        }

        return $this->render('PumukitNewAdminBundle:'.ucfirst($resourceName).':create.html.twig',
                             array(
                                 $resourceName => $resource,
                                 'form' => $form->createView(),
                             ));
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
        $form = $this->getForm($resource);

        if (in_array($request->getMethod(), array('POST', 'PUT', 'PATCH')) && $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            try {
                $dm->persist($resource);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
        }

        return $this->render('PumukitNewAdminBundle:'.ucfirst($resourceName).':update.html.twig',
                             array(
                                 $resourceName => $resource,
                                 'form' => $form->createView(),
                             ));
    }

    /**
     * Clone the given resource.
     */
    public function copyAction(Request $request)
    {
        $resource = $this->findOr404($request);

        $new_resource = $resource->cloneResource();

        $this->create($new_resource);

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
                             array($this->getResourceName() => $data)
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

        $criteria = $this->getCriteria($request->get('criteria', array()));
        $resources = $this->getResources($request, $criteria);

        return $this->render('PumukitNewAdminBundle:'.ucfirst($resourceName).':list.html.twig',
                             array($pluralName => $resources)
        );
    }

    /**
     * Overwrite to update the session.
     */
    public function delete($resource)
    {
        $this->get('session')->remove('admin/'.$this->getResourceName().'/id');
        $this->removeAndFlush($resource);
    }

    public function batchDeleteAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');

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

        $criteria = array('id' => $id);

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
        $criteria = $this->get('session')->get('admin/'.$this->getResourceName().'/criteria', array());

        $new_criteria = array();
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
     * @param null $resource
     *
     * @return \Symfony\Component\Form\Form
     */
    public function getForm($resource = null)
    {
        $resourceName = $this->getResourceName();
        $formType = 'Pumukit\\NewAdminBundle\\Form\\Type\\'.ucfirst($resourceName).'Type';

        $translator = $this->get('translator');
        $locale = $this->getRequest()->getLocale();

        $form = $this->createForm(new $formType($translator, $locale), $resource);

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
     *
     * TODO: Move to RoleController
     */
    public function exportRolesAction()
    {
        $languages = $this->getParameter('pumukit2.locales');

        $csv = array('id', 'cod', 'xml', 'display');
        foreach ($languages as $language) {
            $csv[] = 'name_'.$language;
        }

        foreach ($languages as $language) {
            $csv[] = 'text_'.$language;
        }

        $csv = implode(';', $csv);
        $csv = $csv.PHP_EOL;

        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $roles = $dm->getRepository('PumukitSchemaBundle:Role')->findAll();
        if (!$roles) {
            throw new \Exception('Not roles found');
        }

        $i = 1;
        foreach ($roles as $rol) {
            $dataCSV = array();
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

        return new Response($csv, Response::HTTP_OK, array('Content-Disposition' => 'attachment; filename="roles_i18n.csv"'));
    }

    /**
     * @throws \Exception
     *
     * TODO: Move to PermissionProfileController
     */
    public function exportPermissionProfilesAction()
    {
        $csv = array('id', 'name', 'system', 'default', 'scope', 'permissions');
        $csv = implode(';', $csv);
        $csv = $csv.PHP_EOL;

        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $permissionProfiles = $dm->getRepository('PumukitSchemaBundle:PermissionProfile')->findAll();
        if (!$permissionProfiles) {
            throw new \Exception('Not permission profiles found');
        }

        $i = 1;
        foreach ($permissionProfiles as $pProfile) {
            $dataCSV = array();
            $dataCSV[] = $i;
            $dataCSV[] = $pProfile->getName();
            $dataCSV[] = (int) $pProfile->getSystem();
            $dataCSV[] = (int) $pProfile->getDefault();
            $dataCSV[] = $pProfile->getScope();

            $permission = array();
            foreach ($pProfile->getPermissions() as $permissionProfile) {
                $permission[] = $permissionProfile;
            }

            $dataPermission = implode(',', $permission);

            $dataCSV[] = $dataPermission;
            $data = implode(';', $dataCSV);
            $csv .= $data.PHP_EOL;

            ++$i;
        }

        return new Response($csv, Response::HTTP_OK, array('Content-Disposition' => 'attachment; filename="permissionprofiles.csv"'));
    }
}
