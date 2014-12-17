<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\AdminBundle\Form\Type\LinkType;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class LinkController extends Controller
{
    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject")
     * @Template
     */
    public function createAction(MultimediaObject $multimediaObject, Request $request)
    {
        $link = new Link();
        $form = $this->createForm(new LinkType(), $link);
     
        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $multimediaObject = $this->get('pumukitschema.link')->addLinkToMultimediaObject($multimediaObject, $link);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

	    // TODO get to the edit multimedia tab and not metadata tab
            return $this->redirect($this->generateUrl('pumukitadmin_mms_index'));
        }
	
        return array(
		     'link' => $link, 
		     'form' => $form->createView(), 
		     'mm' => $multimediaObject
		     );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template
     */
    public function updateAction(MultimediaObject $multimediaObject, Request $request)
    {
        // TODO - FIX GET DOCTRINE INSTANCE
      
        $link = $this->find($this->getRequest()->get('id'));
        $form = $this->createForm(new LinkType(), $link);
     
        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
	        //$multimediaObject = $this->get('pumukitschema.link')->addLinkToMultimediaObject($multimediaObject, $link);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

	    // TODO get to the edit multimedia tab and not metadata tab
            return $this->redirect($this->generateUrl('pumukitadmin_mms_index'));
        }
	
        return array(
		     'link' => $link, 
		     'form' => $form->createView(), 
		     'mm' => $multimediaObject
		     );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     */
    public function deleteAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.link')->removeLinkFromMultimediaObject($multimediaObject, $this->getRequest()->get('id'));

	$this->addFlash('success', 'delete');

	// TODO get to the edit multimedia tab and not metadata tab
	return $this->redirect($this->generateUrl('pumukitadmin_mms_index'));
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"}) 
     * @Template("PumukitAdminBundle:Link:list.html.twig")
     */
    public function upAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.link')->upLinkInMultimediaObject($multimediaObject, $this->getRequest()->get('id'));

	$this->addFlash('success', 'delete');

	return array(
		     'mmId' => $multimediaObject->getId(), 
		     'links' => $multimediaObject->getLinks()
		     );
    }

    /**
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @Template("PumukitAdminBundle:Link:list.html.twig")
     */
    public function downAction(MultimediaObject $multimediaObject, Request $request)
    {
        $multimediaObject = $this->get('pumukitschema.link')->downLinkInMultimediaObject($multimediaObject, $this->getRequest()->get('id'));

	$this->addFlash('success', 'delete');

	return array(
		     'mmId' => $multimediaObject->getId(), 
		     'links' => $multimediaObject->getLinks()
		     );
    }

    private function find($id)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
	$repo = $this->dm->getRepository('PumukitSchemaBundle:Link');
	$link = $repo->find($id);

	return $link;
    }
}