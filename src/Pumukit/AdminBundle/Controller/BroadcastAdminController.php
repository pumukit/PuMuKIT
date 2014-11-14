<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BroadcastAdminController extends AdminController
{
    /**
     * Overwrite to update the criteria with MongoRegex, and save it in the session
     */
    public function indexAction(Request $request)
    {
        $config = $this->getConfiguration();

        $criteria = $config->getCriteria();
        $sorting = $config->getSorting();

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/'.$config->getResourceName().'/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/'.$config->getResourceName().'/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/'.$config->getResourceName().'/criteria', array());

    //TODO: do upstream
    $new_criteria = array();
        foreach ($criteria as $property => $value) {
            //preg_match('/^\/.*?\/[imxlsu]*$/i', $e)
        if ('' !== $value) {
            $new_criteria[$property] = new \MongoRegex('/'.$value.'/i');
        }
        }
        $criteria = $new_criteria;

        $pluralName = $config->getPluralResourceName();
        $repository = $this->getRepository();

        if ($config->isPaginated()) {
            $resources = $this
                ->getResourceResolver()
                ->getResource($repository, $config, 'createPaginator', array($criteria, $sorting))
            ;

            if ($request->get('page', null)) {
                $this->get('session')->set('admin/'.$config->getResourceName().'/page', $request->get('page', 1));
            }

            $resources
          ->setCurrentPage($this->get('session')->get('admin/'.$config->getResourceName().'/page', 1), true, true)
                ->setMaxPerPage($config->getPaginationMaxPerPage())
            ;
        } else {
            $resources = $this
                ->getResourceResolver()
                ->getResource($repository, $config, 'findBy', array($criteria, $sorting, $config->getLimit()))
            ;
        }

        $view = $this
            ->view()
            ->setTemplate($config->getTemplate('index.html'))
            ->setTemplateVar($pluralName)
            ->setData($resources)
        ;

        return $this->handleView($view);
    }

    public function copyAction(Request $request)
    {
        $resource = $this->findOr404();

        $new_resource = $resource->cloneResource();

        $this->create($new_resource);

        $this->setFlash('success', 'copy');

        $config = $this->getConfiguration();

        return $this->redirectToRoute(
       $config->getRedirectRoute('index'),
       $config->getRedirectParameters()
    );
    }

    /**
     * Overwrite to update the session.
     */
    public function showAction()
    {
        $config = $this->getConfiguration();
        $data = $this->findOr404();

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

        foreach ($ids as $id) {
            $resource = $this->find($id);
            $this->delete($resource);
        }
        $config = $this->getConfiguration();

        $this->setFlash('success', 'delete');
        $this->get('session')->getFlashBag()->add('default', 'changed');

        return $this->redirectToRoute(
        $config->getRedirectRoute('index'),
        $config->getRedirectParameters()
    );
    }

    public function find($id)
    {
        $config = $this->getConfiguration();
        $repository = $this->getRepository();

        $criteria = array('id' => $id);

        return $this->getResourceResolver()->getResource($repository, $config, 'findOneBy', array($criteria));
    }

    /**
     * Change the default broadcast type
     */
    public function defaultAction(Request $request)
    {
        $config = $this->getConfiguration();
        $repository = $this->getRepository();

        $true_resource = $this->findOr404();
        $resources = $this->getResourceResolver()->getResource($repository, $config, 'findAll');

        foreach ($resources as $resource) {
            if (0 !== strcmp($resource->getId(), $true_resource->getId())) {
                $resource->setDefaultSel(false);
            } else {
                $resource->setDefaultSel(true);
            }
            $this->update($resource);
        }
    // TODO fix show flash message after change default broadcast on click
    //$this->get('session')->getFlashBag()->add('success', 'default');
    //return $this->redirect($this->generateUrl('pumukitadmin_broadcast_index'));

        $this->setFlash('success', 'default');

        return new JsonResponse(array('default' => $resource->getId()));
    }
}
