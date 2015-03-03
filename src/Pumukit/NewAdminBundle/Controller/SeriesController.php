<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class SeriesController extends AdminController
{
  /**
   * Overwrite to search criteria with date
   * @Template
   */
  public function indexAction(Request $request)
  {
      $config = $this->getConfiguration();

      $criteria = $this->getCriteria($config);
      $resources = $this->getResources($request, $config, $criteria);

      $update_session = true;
      foreach($resources as $series) {
          if($series->getId() == $this->get('session')->get('admin/series/id')){
              $update_session = false;
          }
      }
 
      if($update_session){
          $this->get('session')->remove('admin/series/id');
      }

      return array('series' => $resources);
  }

  /**
   * Create new resource
   * @Template("PumukitNewAdminBundle:Series:list.html.twig")
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

      return array('series' => $resources);
  }


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

          return $this->render('PumukitNewAdminBundle:Series:list.html.twig',
                               array('series' => $resources)
                               );
      }

      if ($config->isApiRequest()) {
          return $this->handleView($this->view($form));
      }

      // EDIT MULTIMEDIA OBJECT TEMPLATE CONTROLLER SOURCE CODE
      $factoryService = $this->get('pumukitschema.factory');

      $roles = $factoryService->getRoles();
      if (null === $roles){
          throw new \Exception('Not found any role.');
      }

      $parentTags = $factoryService->getParentTags();
      $mmtemplate = $factoryService->getMultimediaObjectTemplate($resource);

      $formMeta = $this->createForm('pumukitnewadmin_mmtemplate_meta', $mmtemplate);

      $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

      $template = '';
      if (MultimediaObject::STATUS_PROTOTYPE === $mmtemplate->getStatus()){
          $template = '_template';
      }
      // end of getting data for multimedia object template in series

      return $this->render('PumukitNewAdminBundle:Series:update.html.twig',
                           array(
                                 'series'        => $resource,
                                 'form'          => $form->createView(),
                                 'mmtemplate'    => $mmtemplate,
                                 'form_meta'     => $formMeta->createView(),
                                 'roles'         => $roles,
                                 'pub_decisions' => $pubDecisionsTags,
                                 'parent_tags'   => $parentTags,
                                 'template'      => $template
                                 )
                           );
  }

  /**
   * Gets the criteria values
   */
  public function getCriteria($config)
  {
      //$criteria = $config->getCriteria();
      $criteria = $this->getRequest()->get('criteria', array());

      if (array_key_exists('reset', $criteria)) {
          $this->get('session')->remove('admin/series/criteria');
      } elseif ($criteria) {
          $this->get('session')->set('admin/series/criteria', $criteria);
      }
      $criteria = $this->get('session')->get('admin/series/criteria', array());

      $new_criteria = array();
      foreach ($criteria as $property => $value) {
          //preg_match('/^\/.*?\/[imxlsu]*$/i', $e)
          if (('' !== $value) && ('title.en' === $property)) {
              $new_criteria[$property] = new \MongoRegex('/'.$value.'/i');
          } elseif (('' !== $value) && ('date' == $property)) {
              if ('' !== $value['from']) $date_from = new \DateTime($value['from']);
              if ('' !== $value['to']) $date_to = new \DateTime($value['to']);
              if (('' !== $value['from']) && ('' !== $value['to']))
                  $new_criteria['public_date'] = array('$gte' => $date_from, '$lt' => $date_to);
              elseif ('' !== $value['from'])
                  $new_criteria['public_date'] = array('$gte' => $date_from);
              elseif ('' !== $value['to'])
                  $new_criteria['public_date'] = array('$lt' => $date_to);
          } elseif (('' !== $value) && ('announce' === $property)) {
              if ('true' === $value) {
                  $new_criteria[$property] = true;
              } elseif ('false' === $value){
                  $new_criteria[$property] = false;
              }
          } elseif(('' !== $value) && ('status' === $property)) {
          } elseif(('' !== $value) && ('_id' === $property)) {
              $new_criteria['_id'] = $value;
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

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list', array()));
    }

    /**
     * Batch delete action
     * Overwrite to delete multimedia objects inside series
     */
    public function batchDeleteAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)){
            $ids = json_decode($ids, true);
        }

        foreach ($ids as $id) {
            $series = $this->find($id);
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
        }
        $this->addFlash('success', 'delete');

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list', array()));
    }

    /**
     * Batch invert announce selected
     */
    public function invertAnnounceAction(Request $request)
    {
        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)){
            $ids = json_decode($ids, true);
        }

        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        foreach ($ids as $id){
            $resource = $this->find($id);
            if ($resource->getAnnounce()){
                $resource->setAnnounce(false);
            }else{
                $resource->setAnnounce(true);
            }
            $dm->persist($resource);
        }
        $dm->flush();

        return $this->redirect($this->generateUrl('pumukitnewadmin_series_list'));
    }
}
