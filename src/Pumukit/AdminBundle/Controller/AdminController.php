<?php

namespace Pumukit\AdminBundle\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sylius\Bundle\ResourceBundle\Event\ResourceEvent;

class AdminController extends ResourceController
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
            $this->get('session')->remove('admin/direct/criteria');
	} elseif ($criteria){
	    $this->get('session')->set('admin/direct/criteria', $criteria);
	}
	$criteria = $this->get('session')->get('admin/direct/criteria', array());

	$new_criteria = array();
	foreach ($criteria as $property => $value) {
	    //preg_match('/^\/.*?\/[imxlsu]*$/i', $e)
	    if ('' !== $value) {
	        $new_criteria[$property] = new \MongoRegex('/' . $value . '/');
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

            $resources
                ->setCurrentPage($request->get('page', 1), true, true)
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
	
	$new_resource = $resource->cloneDirect();
	
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

	$this->get('session')->set('admin/direct/id', $data->getId());

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
        $event = $this->dispatchEvent('pre_delete', $resource);
        if (!$event->isStopped()) {
            $this->get('session')->remove('admin/direct/id');
            $this->removeAndFlush($resource);
        }

        return $event;
    }



    public function batchDeleteAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');

	foreach($ids as $id) {
            $resource = $this->find($id);
	    $this->delete($resource);
	}
	$config = $this->getConfiguration();

	$this->setFlash('success', 'delete');

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

}