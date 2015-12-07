<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\UserClearance;
use Pumukit\SchemaBundle\Security\Clearance;
use Pumukit\NewAdminBundle\Form\Type\UserClearanceType;

/**
 * @Security("has_role('ROLE_SUPER_ADMIN')")
 */
class UserClearanceController extends AdminController
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
        $userClearances = $this->getResources($request, $config, $criteria);

        $clearances = Clearance::$clearanceDescription;
        $scopes = UserClearance::$scopeDescription;

        return array(
                     'userclearances' => $userClearances,
                     'clearances' => $clearances,
                     'scopes' => $scopes
                     );
    }

    /**
     * List action
     *
     * Overwrite to have clearances list
     * @Template()
     */
    public function listAction(Request $request)
    {
        $config = $this->getConfiguration();
        $session = $this->get('session');
        $sorting = $request->get('sorting');

        $criteria = $this->getCriteria($config);
        $userClearances = $this->getResources($request, $config, $criteria);

        $clearances = Clearance::$clearanceDescription;
        $scopes = UserClearance::$scopeDescription;

        return array(
                     'userclearances' => $userClearances,
                     'clearances' => $clearances,
                     'scopes' => $scopes
                     );
    }

    /**
     * Create Action
     * Overwrite to give UserClearanceType name correctly
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

        $userClearance = new UserClearance();
        $form = $this->getForm($userClearance);

        if ($form->handleRequest($request)->isValid()) {
            try {
                $dm->persist($userClearance);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array("status" => $e->getMessage()), 409);
            }
            if (null === $userClearance) {
                return $this->redirect($this->generateUrl('pumukitnewadmin_userclearance_list'));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_userclearance_list'));
        }

        return array(
                     'userclearance' => $userClearance,
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
        $userClearanceService = $this->get('pumukitschema.userclearance');
        $config = $this->getConfiguration();

        $userClearance = $this->findOr404($request);
        $form     = $this->getForm($userClearance);

        if (in_array($request->getMethod(), array('POST', 'PUT', 'PATCH')) && $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            try {
                $userClearance = $userClearanceService->update($userClearance);
            } catch (\Exception $e) {
                return new JsonResponse(array("status" => $e->getMessage()), 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_userclearance_list'));
        }

        return array(
                     'userclearance' => $userClearance,
                     'form' => $form->createView()
                     );
    }

    /**
     * Overwrite to get form with translations
     * @param object|null $userClearance
     *
     * @return FormInterface
     */
    public function getForm($userClearance = null)
    {
        $translator = $this->get('translator');
        $locale = $this->getRequest()->getLocale();

        $form = $this->createForm(new UserClearanceType($translator, $locale), $userClearance);

        return $form;
    }

    /**
     * Delete action
     *
     * Overwrite to change default user clearance
     * if the default one is being deleted
     */
    public function deleteAction(Request $request)
    {
        $config = $this->getConfiguration();
        $resource = $this->findOr404($request);
        $resourceId = $resource->getId();
        $changeDefault = $resource->isDefault();

        $this->get('pumukitschema.factory')->deleteResource($resource);
        if ($resourceId === $this->get('session')->get('admin/userclearance/id')){
            $this->get('session')->remove('admin/userclearance/id');
        }

        $newDefault = $this->get('pumukitschema.userclearance')->checkDefault($resource);

        return $this->redirect($this->generateUrl('pumukitnewadmin_userclearance_list'));
    }
}