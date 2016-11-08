<?php

namespace Pumukit\NewAdminBundle\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
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
        $config = $this->getConfiguration();

        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);

        $pluralName = $config->getPluralResourceName();

        $view = $this
            ->view()
            ->setTemplate($config->getTemplate('index.html'))
            ->setTemplateVar($pluralName)
            ->setData($resources)
        ;

        return $this->handleView($view);
    }

    /**
     * Create Action
     * Overwrite to return list and not index
     * and show toast message.
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $config = $this->getConfiguration();
        $resourceName = $config->getResourceName();

        $resource = $this->createNew();
        $form = $this->getForm($resource);

        if ($form->handleRequest($request)->isValid()) {
            try {
                $dm->persist($resource);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), 409);
            }

            if ($this->config->isApiRequest()) {
                return $this->handleView($this->view($resource, 201));
            }

            if (null === $resource) {
                return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
        }

        if ($this->config->isApiRequest()) {
            return $this->handleView($this->view($form));
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
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $config = $this->getConfiguration();
        $resourceName = $config->getResourceName();

        $resource = $this->findOr404($request);
        $form = $this->getForm($resource);

        if (in_array($request->getMethod(), array('POST', 'PUT', 'PATCH')) && $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            try {
                $dm->persist($resource);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), 409);
            }

            if ($this->config->isApiRequest()) {
                return $this->handleView($this->view($resource, 204));
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
        }

        if ($this->config->isApiRequest()) {
            return $this->handleView($this->view($form));
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

        $this->domainManager->create($new_resource);

        $this->addFlash('success', 'copy');

        $config = $this->getConfiguration();

        return $this->redirectToRoute(
            $config->getRedirectRoute('index'),
            $config->getRedirectParameters()
        );
    }

    /**
     * Overwrite to update the session.
     */
    public function showAction(Request $request)
    {
        $config = $this->getConfiguration();
        $data = $this->findOr404($request);

        $this->get('session')->set('admin/'.$config->getResourceName().'/id', $data->getId());

        $view = $this
            ->view()
            ->setTemplate($config->getTemplate('show.html'))
            ->setTemplateVar($config->getResourceName())
            ->setData($data)
        ;

        return $this->handleView($view);
    }

    /**
     * Delete action.
     */
    public function deleteAction(Request $request)
    {
        $config = $this->getConfiguration();
        $resource = $this->findOr404($request);
        $resourceId = $resource->getId();
        $resourceName = $config->getResourceName();

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
        $config = $this->getConfiguration();
        $pluralName = $config->getPluralResourceName();
        $resourceName = $config->getResourceName();
        $session = $this->get('session');

        $sorting = $request->get('sorting');

        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);

        return $this->render('PumukitNewAdminBundle:'.ucfirst($resourceName).':list.html.twig',
            array($pluralName => $resources)
        );
    }

    /**
     * Overwrite to update the session.
     */
    public function delete($resource)
    {
        $config = $this->getConfiguration();
        $event = $this->dispatchEvent('pre_delete', $resource);
        if (!$event->isStopped()) {
            $this->get('session')->remove('admin/'.$config->getResourceName().'/id');
            $this->removeAndFlush($resource);
        }

        return $event;
    }

    public function batchDeleteAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $config = $this->getConfiguration();
        $resourceName = $config->getResourceName();

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
        $config = $this->getConfiguration();
        $repository = $this->getRepository();

        $criteria = array('id' => $id);

        return $this->resourceResolver->getResource($repository, 'findOneBy', array($criteria));
    }

    /**
     * Gets the criteria values.
     */
    public function getCriteria($config)
    {
        $criteria = $config->getCriteria();

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/'.$config->getResourceName().'/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/'.$config->getResourceName().'/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/'.$config->getResourceName().'/criteria', array());

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
    public function getResources(Request $request, $config, $criteria)
    {
        $sorting = $config->getSorting();
        $repository = $this->getRepository();
        $session = $this->get('session');
        $session_namespace = 'admin/'.$config->getResourceName();

        if ($config->isPaginated()) {
            $resources = $this
                ->resourceResolver
                ->getResource($repository, 'createPaginator', array($criteria, $sorting));

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
        } else {
            $resources = $this
                ->resourceResolver
                ->getResource($repository, 'findBy', array($criteria, $sorting, $config->getLimit()));
        }

        return $resources;
    }

    /**
     * Overwrite to get form with translations.
     *
     * @param object|null $resource
     *
     * @return FormInterface
     */
    public function getForm($resource = null)
    {
        $formName = $this->config->getFormType();
        $prefix = 'pumukitnewadmin_';
        $formType = 'Pumukit\\NewAdminBundle\\Form\\Type\\'.ucfirst(substr($formName, strlen($prefix))).'Type';

        $translator = $this->get('translator');
        $locale = $this->getRequest()->getLocale();

        $form = $this->createForm(new $formType($translator, $locale), $resource);

        return $form;
    }
}
