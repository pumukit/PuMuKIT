<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class SeriesAdminController extends AdminController
{
    /**
   * Overwrite to search criteria with date
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
      if (('' !== $value) && ('date' !== $property)) {
          $new_criteria[$property] = new \MongoRegex('/'.$value.'/i');
      } elseif (('' !== $value) && ('date' == $property)) {
          $date_from = new \DateTime($value['from']);
          $date_to = new \DateTime($value['to']);
          $new_criteria[$property] = ['$gte' => $date_from, '$lt' => $date_to];
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

  // TODO
  /**
   * Create new resource
   */
  public function createAction(Request $request)
  {
      $config = $this->getConfiguration();

      $resource = $this->createNew();

      $event = $this->create($resource);
      if (!$event->isStopped()) {
          $this->setFlash('success', 'create');

          return $this->redirectTo($resource);
      }

      $this->setFlash($event->getMessageType(), $event->getMessage(), $event->getMessageParams());

      $view = $this
      ->view()
      ->setTemplate($config->getTemplate('index.html'))
      ->setTemplateVar($config->getResourceName())
      ->setData($resource)
      ;

      return $this->handleView($view);
  }

  // TODO
  /**
   * Display the form for editing or update the resource.
   */
  public function updateAction(Request $request)
  {
      $config = $this->getConfiguration();

      $resource = $this->findOr404();
      $form = $this->getForm($resource);

      $view = $this
      ->view()
      ->setTemplate($config->getTemplate('update.html'))
      ->setData(array(
              $config->getResourceName() => $resource,
              'form'                     => $form->createView(),
              ))
      ;

      return $this->handleView($view);
  }
}
