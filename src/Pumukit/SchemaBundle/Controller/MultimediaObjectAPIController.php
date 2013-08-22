<?php

namespace Pumukit\SchemaBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Pumukit\SchemaBundle\Entity\MultimediaObject;
use Pumukit\SchemaBundle\Form\MultimediaObjectType;



class MultimediaObjectAPIController extends FOSRestController
{
    /**
     * @ApiDoc(
     *     resource=true,
     *     section="PumukitSchemaBundle",
     *     description="Get all multimedia objects"
     * )
     */
    public function getMultimediaObjectsAction()
    {
        $cpus = $this->getDoctrine()->getRepository('PumukitSchemaBundle:MultimediaObject')->findAll();
        
	$view = $this->view($cpus, 200)
	  ->setTemplate("PumukitSchemaBundle:Multimediaobjects:index.html.twig")
	  ->setTemplateVar('multimediaobjects');

	return $this->handleView($view);
    }
    
        /**
     * @ApiDoc(
     *     section="PumukitSchemaBundle",
     *     description="Update multimediaobjects",
     *     input="Pumukit\SchemaBundle\Form\MultimediaObjectType"
     * )
     */
    public function putMultimediaObjectAction(MultimediaObject $multimediaobject)
    {
       $em = $this->getDoctrine()->getManager();
                
       $form   = $this->createForm(new MultimediaObjectType(), $multimediaobject);
       $form->bind($this->getRequest());
        
         if ($form->isValid()) {
            $em->persist($multimediaobject);
            $em->flush();
            
            $view = $this->redirectView(
                    $this->generateUrl('get_multimediaobject', array('multimediaobject' => $multimediaobject->getId(), '_format' => 'json'), true),
                    201);
         } else{
	  $view = $this->view($form, 400)
	      ->setTemplate("PumukitSchemaBundle:MultimediaObjects:index.html.twig");
         }

	return $this->handleView($view);

    }
   
}
