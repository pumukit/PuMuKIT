<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class SeriesAdminController extends AdminController
{
  /**
   * Overwrite to search criteria with date
   */
  public function indexAction(Request $request)
  {
      $config = $this->getConfiguration();

      $criteria = $this->getCriteria($config);
      $resources = $this->getResources($request, $config, $criteria);

      if ((0 === count($resources)) && (null !== $this->get('session')->get('admin/series/id'))){
          $this->get('session')->remove('admin/series/id');
      }

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
   * Create new resource
   */
  public function createAction(Request $request)
  {
      $config = $this->getConfiguration();
      $pluralName = $config->getPluralResourceName();

      $series = $this->get('pumukitschema.factory');
      $series->createSeries();

      $this->addFlash('success', 'create');

      $criteria = $this->getCriteria($config);
      $resources = $this->getResources($request, $config, $criteria);

      $view = $this
    ->view()
    ->setTemplate($config->getTemplate('list.html'))
    ->setTemplateVar($pluralName)
    ->setData($resources)
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

      $resource = $this->findOr404($request);
      $this->get('session')->set('admin/series/id', $request->get('id'));
      $form = $this->getForm($resource);

      $method = $request->getMethod();
      if (in_array($method, array('POST', 'PUT', 'PATCH')) &&
      $form->submit($request, !$request->isMethod('PATCH'))->isValid()) {
          $this->domainManager->update($resource);

          if ($config->isApiRequest()) {
              return $this->handleView($this->view($form));
          }

          $criteria = $this->getCriteria($config);
          $resources = $this->getResources($request, $config, $criteria);

          $pluralName = $config->getPluralResourceName();

          $view = $this
      ->view()
      ->setTemplate($config->getTemplate('list.html'))
      ->setTemplateVar($pluralName)
      ->setData($resources)
      ;

          return $this->handleView($view);
      }

      if ($config->isApiRequest()) {
          return $this->handleView($this->view($form));
      }

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
      if (('' !== $value) && ('date' !== $property)) {
          $new_criteria[$property] = new \MongoRegex('/'.$value.'/i');
      } elseif (('' !== $value) && ('date' == $property)) {
          $date_from = new \DateTime($value['from']);
          $date_to = new \DateTime($value['to']);
          $new_criteria[$property] = array('$gte' => $date_from, '$lt' => $date_to);
      }
      }

      return $new_criteria;
  }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function deleteAction(Request $request)
    {
        $config = $this->getConfiguration();

        $series = $this->findOr404($request);
        $seriesId = $series->getId();

        $factoryService = $this->get('pumukitschema.factory');
        $factoryService->deleteSeries($series);

        $seriesSessionId = $this->get('session')->get('admin/mms/id');
        if ($seriesId === $seriesSessionId){
            $this->get('session')->remove('admin/series/id');
        }

        $mmSessionId = $this->get('session')->get('admin/mms/id');
        if ($mmSessionId){
            $mm = $factoryService->findMultimediaObjectById($mmSessionId);
            if ($seriesId === $mm->getSeries()->getId()){
                $this->get('session')->remove('admin/mms/id');
            }
        }

        if ($config->isApiRequest()) {
            return $this->handleView($this->view());
        }

        return $this->redirect($this->generateUrl('pumukitadmin_series_index', array()));
    }
}
