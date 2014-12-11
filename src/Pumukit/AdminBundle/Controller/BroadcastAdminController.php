<?php

namespace Pumukit\AdminBundle\Controller;

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

    public function batchDeleteAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');

        foreach ($ids as $id) {
            $resource = $this->find($id);
            $this->domainManager->delete($resource);
        }
        $config = $this->getConfiguration();

        $this->addFlash('success', 'delete');
        $this->get('session')->getFlashBag()->add('default', 'changed');

        return $this->redirectToRoute(
        $config->getRedirectRoute('index'),
        $config->getRedirectParameters()
    );
    }

    /**
     * Change the default broadcast type
     */
    public function defaultAction(Request $request)
    {
        $config = $this->getConfiguration();
        $repository = $this->getRepository();

        $true_resource = $this->findOr404($request);
        $resources = $this->resourceResolver->getResource($repository, 'findAll');

        foreach ($resources as $resource) {
            if (0 !== strcmp($resource->getId(), $true_resource->getId())) {
                $resource->setDefaultSel(false);
            } else {
                $resource->setDefaultSel(true);
            }
            $this->domainManager->update($resource);
        }
    // TODO fix show flash message after change default broadcast on click
    //$this->get('session')->getFlashBag()->add('success', 'default');
    //return $this->redirect($this->generateUrl('pumukitadmin_broadcast_index'));

        $this->addFlash('success', 'default');

        return new JsonResponse(array('default' => $resource->getId()));
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

      return  $new_criteria;
  }
}
