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


class CpusController extends FOSRestController {

    /**
     * @ApiDoc(
     *     resource=true,
     *     section="PumukitTranscoBundle",
     *     description="This is a description of your API method"
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
     *     description="This is a description of your API method"
     * )
     */
    public function getCpuAction(Cpu $cpu)
    {
	$view = $this->view($cpu, 200)
	  ->setTemplate("PumukitTranscoBundle:Cpus:show.html.twig")
	  ->setTemplateVar('cpu');

	return $this->handleView($view);
    }
    
}

?>
