<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Security("is_granted('ROLE_ACCESS_BROADCASTS')")
 */
class BroadcastController extends AdminController implements NewAdminController
{
    /**
     * Change the default broadcast type.
     */
    public function defaultAction(Request $request)
    {
        $this->checkCreateBroadcastDisabled();

        $repository = $this->getRepository();

        $true_broadcast = $this->findOr404($request);
        $broadcasts = $this->resourceResolver->getResource($repository, 'findAll');

        foreach ($broadcasts as $broadcast) {
            if (0 !== strcmp($broadcast->getId(), $true_broadcast->getId())) {
                $broadcast->setDefaultSel(false);
            } else {
                $broadcast->setDefaultSel(true);
            }
            $this->domainManager->update($broadcast);
        }

        $this->addFlash('success', 'default');

        return new JsonResponse(array('default' => $broadcast->getId()));
    }

    /**
     * Overwrite to check Broadcast creation.
     *
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $this->checkCreateBroadcastDisabled();

        $config = $this->getConfiguration();

        $criteria = $this->getCriteria($config);
        $broadcasts = $this->getResources($request, $config, $criteria);

        return array('broadcasts' => $broadcasts);
    }

    /**
     * Create Action
     * Overwrite to check Broadcast creation.
     *
     * @Template()
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $this->checkCreateBroadcastDisabled();

        $dm = $this->get('doctrine_mongodb')->getManager();
        $config = $this->getConfiguration();

        $broadcast = $this->createNew();
        $form = $this->getForm($broadcast, $request->getLocale());

        if ($form->handleRequest($request)->isValid()) {
            try {
                $dm->persist($broadcast);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), 409);
            }

            if ($this->config->isApiRequest()) {
                return $this->handleView($this->view($broadcast, 201));
            }

            if (null === $broadcast) {
                return $this->redirect($this->generateUrl('pumukitnewadmin_broadcast_list'));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_broadcast_list'));
        }

        if ($this->config->isApiRequest()) {
            return $this->handleView($this->view($form));
        }

        return array(
                     'broadcast' => $broadcast,
                     'form' => $form->createView(),
                     );
    }

    /**
     * Update Action
     * Overwrite to check Broadcast creation.
     *
     * @Template()
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request)
    {
        $this->checkCreateBroadcastDisabled();

        $dm = $this->get('doctrine_mongodb')->getManager();

        $config = $this->getConfiguration();

        $broadcast = $this->findOr404($request);
        $form = $this->getForm($broadcast, $request->getLocale());

        if (in_array($request->getMethod(), array('POST', 'PUT', 'PATCH')) && $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            try {
                $dm->persist($broadcast);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), 409);
            }

            if ($this->config->isApiRequest()) {
                return $this->handleView($this->view($broadcast, 204));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_broadcast_list'));
        }

        if ($this->config->isApiRequest()) {
            return $this->handleView($this->view($form));
        }

        return array(
                     'broadcast' => $broadcast,
                     'form' => $form->createView(),
                     );
    }

    /**
     * Delete action
     * Overwrite to check Broadcast creation.
     */
    public function deleteAction(Request $request)
    {
        $this->checkCreateBroadcastDisabled();

        $broadcast = $this->findOr404($request);
        $broadcastId = $broadcast->getId();

        if (0 !== $broadcast->getNumberMultimediaObjects()) {
            return new Response("Can not delete broadcast '".$broadcast->getName()."', There are Multimedia objects with this broadcast. ", 409);
        }

        $this->get('pumukitschema.factory')->deleteResource($broadcast);
        if ($broadcastId === $this->get('session')->get('admin/broadcast/id')) {
            $this->get('session')->remove('admin/broadcast/id');
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_broadcast_list'));
    }

    /**
     * List action
     * Overwrite to check Broadcast creation.
     *
     * @Template()
     */
    public function listAction(Request $request)
    {
        $this->checkCreateBroadcastDisabled();

        $config = $this->getConfiguration();
        $session = $this->get('session');

        $sorting = $request->get('sorting');

        $criteria = $this->getCriteria($config);
        $broadcasts = $this->getResources($request, $config, $criteria);

        return array('broadcasts' => $broadcasts);
    }

    /**
     * Overwrite to check Broadcast creation.
     */
    public function delete($broadcast)
    {
        $this->checkCreateBroadcastDisabled();

        $event = $this->dispatchEvent('pre_delete', $broadcast);
        if (!$event->isStopped()) {
            $this->get('session')->remove('admin/broadcast/id');
            $this->removeAndFlush($broadcast);
        }

        return $event;
    }

    /**
     * Overwrite to check Broadcast creation.
     */
    public function batchDeleteAction(Request $request)
    {
        $this->checkCreateBroadcastDisabled();

        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $factory = $this->get('pumukitschema.factory');
        foreach ($ids as $id) {
            $broadcast = $this->find($id);
            if (0 !== $broadcast->getNumberMultimediaObjects()) {
                continue;
            }

            try {
                $factory->deleteResource($broadcast);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }
            if ($id === $this->get('session')->get('admin/broadcast/id')) {
                $this->get('session')->remove('admin/broadcast/id');
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_broadcast_list'));
    }

    private function checkCreateBroadcastDisabled()
    {
        $createBroadcastsDisabled = $this->container->getParameter('pumukit_new_admin.disable_broadcast_creation');
        if ($createBroadcastsDisabled) {
            throw $this->createAccessDeniedException();
        }
    }
}
