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
 * @Security("is_granted('ROLE_ACCESS_PERMISSION_PROFILES')")
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

        $createBroadcastsEnabled = !$this->container->getParameter('pumukitschema.disable_broadcast_creation');

        return array(
                     'permissionprofiles' => $permissionProfiles,
                     'permissions' => $permissions,
                     'scopes' => $scopes,
                     'broadcast_enabled' => $createBroadcastsEnabled
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

        $createBroadcastsEnabled = !$this->container->getParameter('pumukitschema.disable_broadcast_creation');

        return array(
                     'permissionprofiles' => $permissionProfiles,
                     'permissions' => $permissions,
                     'scopes' => $scopes,
                     'broadcast_enabled' => $createBroadcastsEnabled
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
        $permissionProfileService = $this->get('pumukitschema.permissionprofile');
        $config = $this->getConfiguration();

        $permissionProfile = new PermissionProfile();
        $form = $this->getForm($permissionProfile);

        if ($form->handleRequest($request)->isValid()) {
            try {
                $permissionProfile = $permissionProfileService->update($permissionProfile, true);
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
        $permissionProfile = $this->findOr404($request);
        $permissionProfileId = $permissionProfile->getId();
        $changeDefault = $permissionProfile->isDefault();

        $response = $this->isAllowedToBeDeleted($permissionProfile);
        if ($response instanceof Response) {
            return $response;
        }

        try {
            $this->get('pumukitschema.factory')->deleteResource($permissionProfile);
            $this->get('pumukitschema.permissionprofile_dispatcher')->dispatchDelete($permissionProfile);
            if ($permissionProfileId === $this->get('session')->get('admin/permissionprofile/id')){
                $this->get('session')->remove('admin/permissionprofile/id');
            }
            $newDefault = $this->get('pumukitschema.permissionprofile')->checkDefault($permissionProfile);
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
    }

    /**
     * Batch update action
     */
    public function batchUpdateAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository('PumukitSchemaBundle:PermissionProfile');

        $selectedDefault = $this->getRequest()->get('selected_default');
        $selectedScopes = $this->getRequest()->get('selected_scopes');
        $checkedPermissions = $this->getRequest()->get('checked_permissions');
        $notCheckedPermissions = $this->getRequest()->get('not_checked_permissions');

        if ('string' === gettype($selectedScopes)){
            $selectedScopes = json_decode($selectedScopes, true);
        }
        if ('string' === gettype($checkedPermissions)){
            $checkedPermissions = json_decode($checkedPermissions, true);
        }
        if ('string' === gettype($notCheckedPermissions)){
            $notCheckedPermissions = json_decode($notCheckedPermissions, true);
        }

        $permissionProfileService = $this->get('pumukitschema.permissionprofile');

        $newDefaultPermissionProfile = $this->find($selectedDefault);
        if (null != $newDefaultPermissionProfile) {
            if (!$newDefaultPermissionProfile->isDefault()) {
                $newDefaultPermissionProfile->setDefault(true);
                $newDefaultPermissionProfile = $permissionProfileService->update($newDefaultPermissionProfile);
            }
        }
        $notSystemPermissionProfiles = $repo->findBySystem(false);
        foreach ($selectedScopes as $selectedScope) {
            $data = $this->separateAttributePermissionProfilesIds($selectedScope);
            $permissionProfile = $this->findPermissionProfile($notSystemPermissionProfiles, $data['profileId']);
            if (null == $permissionProfile) continue;
            try {
                $permissionProfile = $permissionProfileService->setScope($permissionProfile, $data['attribute'], false);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }
        $dm->flush();
        foreach ($checkedPermissions as $checkedPermission) {
            $data = $this->separateAttributePermissionProfilesIds($checkedPermission);
            $permissionProfile = $this->findPermissionProfile($notSystemPermissionProfiles, $data['profileId']);
            if (null == $permissionProfile) continue;
            try {
                $permissionProfile = $permissionProfileService->addPermission($permissionProfile, $data['attribute'], false);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }
        $dm->flush();
        foreach ($notCheckedPermissions as $notCheckedPermission) {
            $data = $this->separateAttributePermissionProfilesIds($notCheckedPermission);
            $permissionProfile = $this->findPermissionProfile($notSystemPermissionProfiles, $data['profileId']);
            if (null == $permissionProfile) continue;
            try {
                $permissionProfile = $permissionProfileService->removePermission($permissionProfile, $data['attribute'], false);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
        }
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_permissionprofile_list'));
    }

    private function separateAttributePermissionProfilesIds($pair='')
    {
        $data = array('attribute' => '', 'profileId' => '');
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

    private function findPermissionProfile($permissionProfiles, $id='')
    {
        foreach ($permissionProfiles as $permissionProfile) {
            if ($id == $permissionProfile->getId()) return $permissionProfile;
        }

        return null;
    }

    private function isAllowedToBeDeleted(PermissionProfile $permissionProfile)
    {
        $userService = $this->get('pumukitschema.user');
        $usersWithPermissionProfile = $userService->countUsersWithPermissionProfile($permissionProfile);

        if (0 < $usersWithPermissionProfile) {
            return new Response('Can not delete this permission profile "'.$permissionProfile->getName().'". There are '.$usersWithPermissionProfile.' user(s) with this permission profile.', Response::HTTP_FORBIDDEN);
        }

        return true;
    }
}