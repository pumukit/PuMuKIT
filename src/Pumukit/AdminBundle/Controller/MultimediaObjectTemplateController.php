<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectTemplateController extends MultimediaObjectController
{
    /**
     * Edit Multimedia Object Template
     *
     * @Template("PumukitAdminBundle:MultimediaObjectTemplate:edit.html.twig")
     */
    public function editAction(Request $request)
    {
        $config = $this->getConfiguration();
        
        $factoryService = $this->get('pumukitschema.factory');

        $roles = $factoryService->getRoles();
        if (null === $roles){
            throw new \Exception('Not found any role.');
        }

        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->get('id'), $sessionId);
        if (null === $series){
            throw new \Exception('Series with id '.$request->get('id').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());
  
        $parentTags = $factoryService->getParentTags();
        $mmtemplate = $factoryService->getMultimediaObjectTemplate($series);
        
        $formMeta = $this->createForm($config->getFormType().'_meta', $mmtemplate);

        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $mmtemplate->getStatus()){
            $template = '_template';
        }
        
        return array(
                     'mmtemplate'    => $mmtemplate,
                     'form_meta'     => $formMeta->createView(),
                     'series'        => $series,
                     'roles'         => $roles,
                     'pub_decisions' => $pubDecisionsTags,
                     'parent_tags'   => $parentTags,
                     'template'      => $template
                     )
          ;
    }
    
    /**
     * Display the form for editing or update the resource.
     */
    public function updatemetaAction(Request $request)
    {
        $config = $this->getConfiguration();

        $factoryService = $this->get('pumukitschema.factory');

        $roles = $factoryService->getRoles();
        if (null === $roles){
            throw new \Exception('Not found any role.');
        }

        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->get('id'), $sessionId);
        if (null === $series){
            throw new \Exception('Series with id '.$request->get('id').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());

        $parentTags = $factoryService->getParentTags();
        $mmtemplate = $factoryService->getMultimediaObjectTemplate($series);

        $formMeta = $this->createForm($config->getFormType().'_meta', $mmtemplate);

        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $method = $request->getMethod();
        if (in_array($method, array('POST', 'PUT', 'PATCH')) &&
            $formMeta->submit($request, !$request->isMethod('PATCH'))->isValid()) {
          $this->domainManager->update($mmtemplate);

          if ($config->isApiRequest()) {
            return $this->handleView($this->view($formMeta));
          }

          return new JsonResponse(array('mmtemplate' => 'updatemeta'));
        }

        if ($config->isApiRequest()) {
          return $this->handleView($this->view($formMeta));
        }

        $view = $this
          ->view()
          ->setTemplate($config->getTemplate('edit.html'))
          ->setData(array(
                          'mm'            => $resource,
                          'form_meta'     => $formMeta->createView(),
                          'series'        => $series,
                          'roles'         => $roles,
                          'pub_decisions' => $pubDecisionsTags,
                          'parent_tags'   => $parentTags,
                          ))
          ;

        return $this->handleView($view);
    }
}
