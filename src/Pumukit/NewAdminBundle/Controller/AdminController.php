<?php

namespace Pumukit\NewAdminBundle\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class AdminController extends ResourceController
{
    /**
     * Overwrite to update the criteria with MongoRegex, and save it in the session
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
     * Clone the given resource
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
     * Delete action
     */
    public function deleteAction(Request $request)
    {
        $config = $this->getConfiguration();
        $resource = $this->findOr404($request);
        $resourceId = $resource->getId();
        $resourceName = $config->getResourceName();

        $this->get('pumukitschema.factory')->deleteResource($resource);
        if ($resourceId === $this->get('session')->get('admin/'.$resourceName.'/id')){
            $this->get('session')->remove('admin/'.$resourceName.'/id');
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_'.$resourceName.'_list'));
    }

    /**
     * List action
     */
    public function listAction(Request $request)
    {
        $config = $this->getConfiguration();
        $pluralName = $config->getPluralResourceName();
        $resourceName = $config->getResourceName();

        $sorting = $request->get('sorting');
        if ((null !== $sorting) && ('series' === $resourceName)){
            $this->get('session')->set('admin/'.$resourceName.'/type', $sorting[key($sorting)]);
            $this->get('session')->set('admin/'.$resourceName.'/sort', key($sorting));
        }

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

        if ('string' === gettype($ids)){
            $ids = json_decode($ids, true);
        }

        foreach ($ids as $id) {
            $resource = $this->find($id);
            $this->domainManager->delete($resource);
        }
        $config = $this->getConfiguration();
        $resourceName = $config->getResourceName();

        $this->addFlash('success', 'delete');

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
   * Gets the criteria values
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

    //TODO: do upstream
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
   * Gets the list of resources according to a criteria
   */
  public function getResources(Request $request, $config, $criteria)
  {
      $sorting = $config->getSorting();
      $repository = $this->getRepository();
      $session = $this->get('session');
      $session_namespace = 'admin/' . $config->getResourceName();

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
              ->setCurrentPage($session->get($session_namespace.'/page', 1), true, true)
              ->setMaxPerPage($session->get($session_namespace.'/paginate', 10));
      } else {
          $resources = $this
              ->resourceResolver
              ->getResource($repository, 'findBy', array($criteria, $sorting, $config->getLimit()));
      }

      return $resources;
  }
}
