<?php

/**
 * Description of CpusController
 *
 * @author Ivan Vazquez <ivan@teltek.es>
 */

namespace Pumukit\TranscoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Pumukit\TranscoBundle\Entity\Cpu;


class CpusController extends Controller {

    /**
     * @return array
     * @View
     * @ApiDoc(
     *     resource=true,
     *     section="PumukitTranscoBundle",
     *     description="This is a description of your API method"
     * )
     */
    public function getCpusAction()
    {
        $cpus = $this->getDoctrine()->getRepository('PumukitTranscoBundle:Cpu')->findAll();
        

        return array('cpus' => $cpus);
    }
    
    /**
     * @param Cpu $cpu
     * @return array
     * @View
     * @ParamConverter("cpu", class="PumukitTranscoBundle:Cpu")
     * @ApiDoc(
     *     section="PumukitTranscoBundle",
     *     description="This is a description of your API method"
     * )
     */
    public function getCpuAction(Cpu $cpu)
    {
        return array('cpu' => $cpu);
    }
    
}

?>
