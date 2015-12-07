<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\NewAdminBundle\Form\Type\PermissionProfileType;

/**
 * @Security("has_role('ROLE_SUPER_ADMIN')")
 */
class PermissionProfileController extends AdminController
{
    /**
     * Overwrite to update the criteria with MongoRegex, and save it in the session
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $config = $this->getConfiguration();
        $session = $this->get('session');
        $sorting = $request->get('sorting');

        $criteria = $this->getCriteria($config);
        $permissionProfiles = $this->getResources($request, $config, $criteria);

        $permissions = Permission::$permissionDescription;
        $scopes = PermissionProfile::$scopeDescription;

        return array(
                     'permissionprofiles' => $permissionProfiles,
                     'permissions' => $permissions,
                     'scopes' => $scopes
                     );
    }

    /**
     * List action
     *
     * Overwrite to have permissions list
     * @Template()
     */
    public function listAction(Request $request)
    {
        $config = $this->getConfiguration();
        $session = $this->get('session');
        $sorting = $request->get('sorting');

        $criteria = $this->getCriteria($config);
        $permissionProfiles = $this->getResources($request, $config, $criteria);

        $permissions = Permission::$permissionDescription;
        $scopes = PermissionProfile::$scopeDescription;

        return array(
                     'permissionprofiles' => $permissionProfiles,
                     'permissions' => $permissions,
                     'scopes' => $scopes
                     );
    }

    /**
     * Create Action
     * Overwrite to give PermissionProfileType name correctly
     * @Template()
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $config = $this->getConfiguration();

        $permissionProfile = new PermissionProfile();
        $form = $this->getForm($permissionProfile);

        if ($form->handleRequest($request)->isValid()) {
            try {
                $dm->persist($permissionProfile);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array("status" => $e->getMessage()), 409);
            }
            if (null === $permissionProfile) {
                return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
        }

        return array(
                     'permissionprofile' => $permissionProfile,
                     'form' => $form->createView()
                     );
    }

    /**
     * Update Action
     * Overwrite to return list and not index
     * and show toast message
     * @Template()
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $permissionProfileService = $this->get('pumukitschema.permissionprofile');
        $config = $this->getConfiguration();

        $permissionProfile = $this->findOr404($request);
        $form     = $this->getForm($permissionProfile);

        if (in_array($request->getMethod(), array('POST', 'PUT', 'PATCH')) && $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            try {
                $permissionProfile = $permissionProfileService->update($permissionProfile);
            } catch (\Exception $e) {
                return new JsonResponse(array("status" => $e->getMessage()), 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
        }

        return array(
                     'permissionprofile' => $permissionProfile,
                     'form' => $form->createView()
                     );
    }

    /**
     * Overwrite to get form with translations
     * @param object|null $permissionProfile
     *
     * @return FormInterface
     */
    public function getForm($permissionProfile = null)
    {
        $translator = $this->get('translator');
        $locale = $this->getRequest()->getLocale();

        $form = $this->createForm(new PermissionProfileType($translator, $locale), $permissionProfile);

        return $form;
    }

    /**
     * Delete action
     *
     * Overwrite to change default user permission
     * if the default one is being deleted
     */
    public function deleteAction(Request $request)
    {
        $config = $this->getConfiguration();
        $resource = $this->findOr404($request);
        $resourceId = $resource->getId();
        $changeDefault = $resource->isDefault();

        $this->get('pumukitschema.factory')->deleteResource($resource);
        if ($resourceId === $this->get('session')->get('admin/permissionprofile/id')){
            $this->get('session')->remove('admin/permissionprofile/id');
        }

        $newDefault = $this->get('pumukitschema.permissionprofile')->checkDefault($resource);

        return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
    }
}