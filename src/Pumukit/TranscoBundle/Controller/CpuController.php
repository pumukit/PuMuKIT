<?php

/**
 * Description of CpusController
 *
 * @author Ivan Vazquez <ivan@teltek.es>
 */

namespace Pumukit\TranscoBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Pumukit\TranscoBundle\Entity\Cpu;
use Pumukit\TranscoBundle\Form\CpuType;


class CpuController extends FOSRestController {

    /**
     * @ApiDoc(
     *     resource=true,
     *     section="PumukitTranscoBundle",
     *     description="Get all cpus"
     * )
     */
    public function getCpusAction()
    {
        $cpus = $this->getDoctrine()->getRepository('PumukitTranscoBundle:Cpu')->findAll();
        
	$view = $this->view($cpus, 200)
	  ->setTemplate("PumukitTranscoBundle:Cpus:index.html.twig")
	  ->setTemplateVar('cpus');

	return $this->handleView($view);
    }
    
    /**
     * @ParamConverter("cpu", class="PumukitTranscoBundle:Cpu")
     * @ApiDoc(
     *     section="PumukitTranscoBundle",
     *     description="Get cpu"
     * )
     */
    public function getCpuAction(Cpu $cpu)
    {
	$view = $this->view($cpu, 200)
	  ->setTemplate("PumukitTranscoBundle:Cpus:show.html.twig")
	  ->setTemplateVar('cpu');

	return $this->handleView($view);
    }
    
   
    /**
     * @ApiDoc(
     *     section="PumukitTranscoBundle",
     *     description="Create new cpu",
     *     input="Pumukit\TranscoBundle\Form\CpuType"
     * )
     */
    public function postCpusAction()
    {
        $em = $this->getDoctrine()->getManager();
                
        $entity = new Cpu();
        $form   = $this->createForm(new CpuType(), $entity);
        $form->bind($this->getRequest());
        
         if ($form->isValid()) {
            $em->persist($entity);
            $em->flush();
            
            $view = $this->redirectView(
                    $this->generateUrl('get_cpu', array('cpu' => $entity->getId(), '_format' => 'json'), true),
                    201);
         } else{
	  $view = $this->view($form, 400)
	      ->setTemplate("PumukitTranscoBundle:Cpus:index.html.twig");
         }

	return $this->handleView($view);
    }
    
    
    /**
     * @ApiDoc(
     *     section="PumukitTranscoBundle",
     *     description="Update cpu",
     *     input="Pumukit\TranscoBundle\Form\CpuType"
     * )
     */
    public function putCpuAction(Cpu $cpu)
    {
       $em = $this->getDoctrine()->getManager();
                
       $form   = $this->createForm(new CpuType(), $cpu);
       $form->bind($this->getRequest());
        
         if ($form->isValid()) {
            $em->persist($cpu);
            $em->flush();
            
            $view = $this->redirectView(
                    $this->generateUrl('get_cpu', array('cpu' => $cpu->getId(), '_format' => 'json'), true),
                    201);
         } else{
	  $view = $this->view($form, 400)
	      ->setTemplate("PumukitTranscoBundle:Cpus:index.html.twig");
         }

	return $this->handleView($view);

    }
    
    /**
     * @ApiDoc(
     *     section="PumukitTranscoBundle",
     *     description="Delete cpu",
     *     input="Pumukit\TranscoBundle\Form\CpuType"
     * )
     */
    public function deleteCpuAction(Cpu $cpu)
    {
        $em = $this->getDoctrine()->getManager();
       
        if (!$cpu) {
            throw $this->createNotFoundException('Unable to find Person entity.');
        }

        $em->remove($cpu);
        $em->flush();
        
        $view = $this->redirectView(
            $this->generateUrl('get_cpus', array('_format' => 'json'), true),
            204);

      	return $this->handleView($view);

    }
}

?>
